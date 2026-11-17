<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\Driver;
use App\Models\foc;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Task;
use App\Models\Trip;
use App\Support\InvoiceDiscount;
use App\Support\OfflineNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Saves documents the driver made on the phone without signal.
 *
 * Rules, in order:
 *  1. Same client_ref seen before -> return the saved document, never a
 *     second copy (a resent upload, or two uploads at the same moment).
 *  2. The phone's own number (IV2611/BK07/0038, the driver's normal series) is kept. If it is malformed
 *     or already used, the next free offline number is given instead, the
 *     old one is kept in renumbered_from and the document is flagged.
 *  3. Data the server would normally refuse (a product made inactive, a
 *     price changed, the trip already ended) does not
 *     reject the document: the customer already has the printed receipt.
 *     It is saved as the phone made it and flagged in review_note for the
 *     office (admin Offline Review page).
 *  4. A document the driver cancelled before it uploaded is still saved,
 *     as cancelled, so its number is not silently missing.
 */
class OfflineDocuments
{
    private const MAX_RENUMBER_TRIES = 5;

    /**
     * @return array result row for the app: client_ref, id, number,
     *               renumbered_from, review_note, duplicate, kind
     */
    public function saveInvoice(Driver $driver, array $item): array
    {
        $ref = (string) $item['client_ref'];

        if ($found = $this->findByRef($ref)) {
            return $found;
        }

        $customer = Customer::query()->find($item['customer_id']);
        if (!$customer) {
            throw new \RuntimeException('Invalid customer');
        }

        $date = $this->documentDate($item['date'] ?? null);
        $type = (int) $item['type'];
        $isDo = $customer->is_do_customer && $type === 2;
        $prefix = $isDo ? 'DO' : ($type === 2 ? 'IV' : 'CS');

        try {
            return $this->withNumber($driver, $prefix, $date, $item['docno'] ?? null, function ($number, array $notes) use ($driver, $customer, $item, $date, $type, $isDo, $ref) {
                return $isDo
                    ? $this->createDeliveryOrder($driver, $customer, $item, $date, $number, $notes, $ref)
                    : $this->createInvoice($driver, $customer, $item, $date, $type, $number, $notes, $ref);
            });
        } catch (QueryException $e) {
            // Another upload of the same document won the race.
            if ($this->isDuplicateKey($e, 'client_ref') && ($found = $this->findByRef($ref))) {
                return $found;
            }
            throw $e;
        }
    }

    public function saveSalesOrder(Driver $driver, array $item): array
    {
        $ref = (string) $item['client_ref'];

        if ($found = $this->findByRef($ref, true)) {
            return $found;
        }

        $customer = Customer::query()->find($item['customer_id']);
        if (!$customer) {
            throw new \RuntimeException('Invalid customer');
        }

        $date = $this->documentDate($item['date'] ?? null);

        try {
            return $this->withNumber($driver, 'SO', $date, $item['docno'] ?? null, function ($number, array $notes) use ($driver, $customer, $item, $date, $ref) {
                return $this->createSalesOrder($driver, $customer, $item, $date, $number, $notes, $ref);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e, 'client_ref') && ($found = $this->findByRef($ref, true))) {
                return $found;
            }
            throw $e;
        }
    }

    /** A document already saved under this request id, as an app result row. */
    public function findByRef(string $ref, bool $salesOrder = false): ?array
    {
        if ($ref === '') {
            return null;
        }

        if ($salesOrder) {
            $so = SalesOrder::query()->where('client_ref', $ref)->first();
            return $so ? $this->result($ref, 'sales_order', $so->id, $so->sono, $so, true) : null;
        }

        $invoice = Invoice::query()->where('client_ref', $ref)->first();
        if ($invoice) {
            return $this->result($ref, 'invoice', $invoice->id, $invoice->invoiceno, $invoice, true);
        }
        $do = DeliveryOrder::query()->where('client_ref', $ref)->first();
        if ($do) {
            return $this->result($ref, 'delivery_order', $do->id, $do->dono, $do, true);
        }

        return null;
    }

    // ------------------------------------------------------------------ numbering

    /**
     * Runs $create inside a transaction with the phone's number, or with the
     * next free offline number when the phone's one cannot be used.
     */
    private function withNumber(Driver $driver, string $prefix, string $date, ?string $phoneNumber, callable $create): array
    {
        $notes = [];
        $renumberedFrom = null;
        $number = $phoneNumber;

        if ($number === null || $number === '') {
            // Older app versions do not send a number: give the next one.
            $number = OfflineNumber::next($prefix, date('ym', strtotime($date)), $driver);
        } elseif (!OfflineNumber::fits($number, $prefix, $driver, $date)) {
            $renumberedFrom = $number;
            $notes[] = "Phone number $number did not match the driver, document type or month, so it was renumbered.";
            $number = OfflineNumber::next($prefix, date('ym', strtotime($date)), $driver);
        }

        for ($try = 0; ; $try++) {
            DB::beginTransaction();
            try {
                $result = $create($number, $notes);
                if ($renumberedFrom !== null) {
                    $this->markRenumbered($result, $renumberedFrom);
                    $result['renumbered_from'] = $renumberedFrom;
                }
                DB::commit();
                return $result;
            } catch (QueryException $e) {
                DB::rollBack();
                if (!$this->isNumberClash($e) || $try >= self::MAX_RENUMBER_TRIES) {
                    throw $e;
                }
                // The number is already used (e.g. the driver logged in on
                // another phone while this one was offline): never save a
                // second document under it. Take the next free one and flag.
                if ($renumberedFrom === null) {
                    $renumberedFrom = $number;
                    $notes[] = "Number $number was already used, so this document was renumbered. Tell the customer the new number.";
                }
                $number = OfflineNumber::next($prefix, date('ym', strtotime($date)), $driver);
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    private function markRenumbered(array $result, string $from): void
    {
        $model = ['invoice' => Invoice::class, 'delivery_order' => DeliveryOrder::class, 'sales_order' => SalesOrder::class][$result['kind']];
        $model::query()->where('id', $result['id'])->update(['renumbered_from' => $from]);
    }

    private function isNumberClash(QueryException $e): bool
    {
        return $this->isDuplicateKey($e, 'invoiceno') || $this->isDuplicateKey($e, 'dono') || $this->isDuplicateKey($e, 'sono');
    }

    private function isDuplicateKey(QueryException $e, string $column): bool
    {
        // MySQL 1062: Duplicate entry '...' for key 'invoices_invoiceno_unique'
        return ($e->errorInfo[1] ?? null) == 1062 && str_contains($e->getMessage(), $column . '_unique');
    }

    // ------------------------------------------------------------------ documents

    private function createInvoice(Driver $driver, Customer $customer, array $item, string $date, int $type, string $number, array $notes, string $ref): array
    {
        $cancelled = !empty($item['cancelled']);
        [$trip, $lorryId] = $this->tripAt($driver, $date, $notes);
        $this->noteCustomer($customer, $notes);

        $invoice = new Invoice();
        $invoice->date = $date;
        $invoice->invoiceno = $number;
        $invoice->client_ref = $ref;
        $invoice->is_offline = true;
        $invoice->uploaded_at = now();
        $invoice->customer_id = $customer->id;
        $invoice->driver_id = $driver->id;
        $invoice->kelindan_id = $trip->kelindan_id ?? null;
        $invoice->agent_id = $customer->agent_id;
        $invoice->supervisor_id = $customer->supervisor_id;
        $invoice->paymentterm = $type;
        $invoice->status = $cancelled ? 2 : 1;
        $invoice->chequeno = $item['cheque_no'] ?? null;
        $invoice->remark = $item['remark'] ?? null;
        $invoice->trip_id = $trip->id ?? null;
        $invoice->save();

        $total = 0;
        foreach ($item['invoicedetail'] as $line) {
            if (InvoiceDiscount::isDiscountProduct($line['product_id'])) {
                continue;
            }
            $this->noteProduct($line, $customer, $notes);

            $detail = new InvoiceDetail();
            $detail->invoice_id = $invoice->id;
            $detail->product_id = $line['product_id'];
            $detail->quantity = $line['quantity'];
            $detail->price = $line['price'];
            $detail->totalprice = $line['quantity'] * $line['price'];
            $detail->remark = !empty($line['foc']) ? 'FOC' : null;
            $detail->save();
            $total += $detail->totalprice;

            if ($cancelled) {
                continue;
            }
            if (empty($line['foc'])) {
                $this->countFoc($customer, $line, $date);
            }
            $this->takeStock($driver, $lorryId, $trip, $line, $number, $date, $notes);
        }

        $total -= InvoiceDiscount::set($invoice->id, $item['discount'] ?? 0);

        if (!$cancelled && in_array($type, [1, 3, 4], true)) {
            $payment = new InvoicePayment();
            $payment->invoice_id = $invoice->id;
            $payment->type = $type;
            $payment->customer_id = $invoice->customer_id;
            $payment->amount = $total;
            $payment->status = 1;
            $payment->driver_id = $driver->id;
            $payment->approve_by = $driver->name;
            $payment->approve_at = $date;
            if ($type === 1 && isset($item['cash_received']) && is_numeric($item['cash_received']) && $item['cash_received'] >= $total) {
                $payment->cash_received = $item['cash_received'];
            }
            $payment->save();
        }

        if (!$cancelled) {
            $this->markVisited($driver, $customer->id, $date);
        }
        $this->saveNotes(Invoice::class, $invoice->id, $notes);

        return $this->result($ref, 'invoice', $invoice->id, $number, null, false, $notes);
    }

    private function createDeliveryOrder(Driver $driver, Customer $customer, array $item, string $date, string $number, array $notes, string $ref): array
    {
        $cancelled = !empty($item['cancelled']);
        [$trip] = $this->tripAt($driver, $date, $notes);
        $this->noteCustomer($customer, $notes);

        $do = new DeliveryOrder();
        $do->dono = $number;
        $do->client_ref = $ref;
        $do->is_offline = true;
        $do->uploaded_at = now();
        $do->date = $date;
        $do->customer_id = $customer->id;
        $do->driver_id = $driver->id;
        $do->kelindan_id = $trip->kelindan_id ?? null;
        $do->agent_id = $customer->agent_id;
        $do->supervisor_id = $customer->supervisor_id;
        $do->paymentterm = 2;
        $do->status = $cancelled ? 2 : 0;
        $do->remark = $item['remark'] ?? null;
        $do->chequeno = $item['cheque_no'] ?? null;
        $do->trip_id = $trip->id ?? null;
        $do->save();

        foreach ($item['invoicedetail'] as $line) {
            if (InvoiceDiscount::isDiscountProduct($line['product_id'])) {
                continue;
            }
            $this->noteProduct($line, $customer, $notes);

            $detail = new DeliveryOrderDetail();
            $detail->deliveryorder_id = $do->id;
            $detail->product_id = $line['product_id'];
            $detail->quantity = $line['quantity'];
            $detail->price = $line['price'];
            $detail->totalprice = $line['quantity'] * $line['price'];
            $detail->save();
        }

        if (!$cancelled) {
            $this->markVisited($driver, $customer->id, $date);
        }
        $this->saveNotes(DeliveryOrder::class, $do->id, $notes);

        return $this->result($ref, 'delivery_order', $do->id, $number, null, false, $notes);
    }

    private function createSalesOrder(Driver $driver, Customer $customer, array $item, string $date, string $number, array $notes, string $ref): array
    {
        $cancelled = !empty($item['cancelled']);
        // An SO does not need a trip (orders are taken at night too).
        $trip = $this->openTripAt($driver, $date);
        $this->noteCustomer($customer, $notes);

        $so = new SalesOrder();
        $so->sono = $number;
        $so->client_ref = $ref;
        $so->is_offline = true;
        $so->uploaded_at = now();
        $so->date = $date;
        $so->customer_id = $customer->id;
        $so->driver_id = $driver->id;
        $so->kelindan_id = $trip->kelindan_id ?? null;
        $so->agent_id = $customer->agent_id;
        $so->supervisor_id = $customer->supervisor_id;
        $so->status = $cancelled ? 2 : 0;
        $so->remark = $item['remark'] ?? null;
        $so->trip_id = $trip->id ?? null;
        $so->off_day = (bool) $customer->off_day;
        $so->save();

        foreach ($item['salesorderdetail'] as $line) {
            $this->noteProduct($line, $customer, $notes);

            // The printed SO shows the phone's price, so keep that price.
            $price = !empty($line['foc']) ? 0 : $line['price'];
            $detail = new SalesOrderDetail();
            $detail->sales_order_id = $so->id;
            $detail->product_id = $line['product_id'];
            $detail->quantity = $line['quantity'];
            $detail->price = $price;
            $detail->totalprice = $line['quantity'] * $price;
            $detail->remark = !empty($line['foc']) ? 'FOC' : null;
            $detail->save();
        }

        if (!$cancelled) {
            $this->markVisited($driver, $customer->id, $date);
        }
        $this->saveNotes(SalesOrder::class, $so->id, $notes);

        return $this->result($ref, 'sales_order', $so->id, $number, null, false, $notes);
    }

    // ------------------------------------------------------------------ helpers

    private function documentDate(?string $date): string
    {
        if ($date && ($time = strtotime($date)) !== false) {
            return date('Y-m-d H:i:s', $time);
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * The trip the driver was on when the document was made. Uploads after
     * End Trip still land on the trip they belong to. Returns [trip|null,
     * lorry id for the stock].
     */
    private function tripAt(Driver $driver, string $date, array &$notes): array
    {
        $trip = $this->openTripAt($driver, $date);
        if ($trip) {
            return [$trip, $trip->lorry_id];
        }

        $latest = Trip::where('driver_id', $driver->id)->where('type', 1)->orderBy('date', 'desc')->first();
        $notes[] = 'No trip was open at ' . $date . '; saved on ' . ($latest ? 'the latest trip.' : 'no trip.');

        return [$latest, $latest->lorry_id ?? $driver->lorry_id];
    }

    private function openTripAt(Driver $driver, string $date): ?Trip
    {
        // trips has a type-1 row when a trip starts and a type-2 row when it
        // ends. The latest row at or before $date tells which state it was.
        $row = Trip::where('driver_id', $driver->id)
            ->where('date', '<=', $date)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return ($row && (int) $row->type === 1) ? $row : null;
    }

    private function noteCustomer(Customer $customer, array &$notes): void
    {
        if ((int) $customer->status !== 1) {
            $notes[] = "Customer {$customer->company} is inactive.";
        }
    }

    private function noteProduct(array $line, Customer $customer, array &$notes): void
    {
        $product = Product::query()->find($line['product_id']);
        if (!$product) {
            $notes[] = "Product id {$line['product_id']} does not exist.";
            return;
        }
        if ((int) $product->status !== 1) {
            $notes[] = "Product {$product->name} is inactive.";
        }
        if (empty($line['foc'])) {
            $current = Product::priceForCustomer($product->id, $product->price, $customer->id);
            if (abs((float) $current - (float) $line['price']) > 0.001) {
                $notes[] = "Product {$product->name}: phone price RM" . number_format((float) $line['price'], 2)
                    . ', current price RM' . number_format((float) $current, 2) . '.';
            }
        }
    }

    private function countFoc(Customer $customer, array $line, string $date): void
    {
        $foc = foc::where('customer_id', $customer->id)
            ->where('product_id', $line['product_id'])
            ->where('startdate', '<=', $date)
            ->where('enddate', '>', $date)
            ->where('status', 1)
            ->first();
        if ($foc) {
            $achieved = $foc->achievequantity + $line['quantity'];
            $foc->update(['achievequantity' => $achieved, 'status' => $achieved >= $foc->quantity ? 0 : 1]);
        }
    }

    private function takeStock(Driver $driver, $lorryId, ?Trip $trip, array $line, string $number, string $date, array &$notes): void
    {
        if (!$lorryId) {
            $notes[] = 'No lorry, stock not deducted.';
            return;
        }
        $balance = InventoryBalance::where('lorry_id', $lorryId)->where('product_id', $line['product_id'])->first();
        if (!$balance) {
            $balance = new InventoryBalance();
            $balance->lorry_id = $lorryId;
            $balance->product_id = $line['product_id'];
            $balance->quantity = 0;
        }
        $balance->quantity = $balance->quantity - $line['quantity'];
        // Stock below zero is allowed, the same as an online sale (the
        // requirement's "Negative Stock Transaction"), so it is not flagged.
        $balance->save();

        $tx = new InventoryTransaction();
        $tx->lorry_id = $lorryId;
        $tx->product_id = $line['product_id'];
        $tx->quantity = $line['quantity'] * -1;
        $tx->type = 3;
        $tx->remark = $number;
        $tx->user = $driver->employeeid . ' (' . $driver->name . ')';
        $tx->date = $date;
        $tx->trip_id = $trip->id ?? null;
        $tx->save();
    }

    private function markVisited(Driver $driver, $customerId, string $date): void
    {
        Task::where('customer_id', $customerId)
            ->where('driver_id', $driver->id)
            ->where('date', date('Y-m-d', strtotime($date)))
            ->whereIn('status', [0, 1])
            ->update(['status' => 8]);
    }

    private function saveNotes(string $model, $id, array $notes): void
    {
        if ($notes) {
            $model::query()->where('id', $id)->update(['review_note' => implode("\n", array_unique($notes))]);
        }
    }

    private function result(string $ref, string $kind, $id, string $number, $model = null, bool $duplicate = false, array $notes = []): array
    {
        $note = $model ? $model->review_note : ($notes ? implode("\n", array_unique($notes)) : null);

        return [
            'client_ref' => $ref,
            'kind' => $kind,
            'id' => $id,
            // Older apps read these two names.
            'invoice_id' => $kind === 'sales_order' ? null : $id,
            'sales_order_id' => $kind === 'sales_order' ? $id : null,
            'invoiceno' => $kind === 'sales_order' ? null : $number,
            'sono' => $kind === 'sales_order' ? $number : null,
            'number' => $number,
            'renumbered_from' => $model ? $model->renumbered_from : null,
            'review_note' => $note,
            'is_delivery_order' => $kind === 'delivery_order',
            'duplicate' => $duplicate,
        ];
    }
}
