<?php

namespace App\Http\Controllers;

use App\DataTables\DeliveryOrderDataTable;
use App\Http\Requests\CreateDeliveryOrderRequest;
use App\Http\Requests\UpdateDeliveryOrderRequest;
use App\Repositories\DeliveryOrderRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Customer;
use App\Models\Code;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class DeliveryOrderController extends AppBaseController
{
    /** @var DeliveryOrderRepository $deliveryOrderRepository*/
    private $deliveryOrderRepository;

    public function __construct(DeliveryOrderRepository $deliveryOrderRepo)
    {
        $this->deliveryOrderRepository = $deliveryOrderRepo;
    }

    public function index(Request $request, DeliveryOrderDataTable $deliveryOrderDataTable)
    {
        return $deliveryOrderDataTable->render('delivery_orders.index');
    }

    public function create()
    {
        return view('delivery_orders.create');
    }

    public function store(CreateDeliveryOrderRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if (empty($input['dono'])) {
            $input['dono'] = Code::nextDocumentNumber('DO', $input['driver_id'] ?? null);
        }

        $deliveryOrder = $this->deliveryOrderRepository->create($input);

        Flash::success('Delivery Order saved successfully.');

        if ($input['method'] == 1) {
            return redirect(route('deliveryOrders.index'));
        } else {
            return redirect(route('deliveryOrders.show', encrypt($deliveryOrder->id)));
        }
    }

    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $deliveryorderdetails = DeliveryOrderDetail::with('product')->where('deliveryorder_id', $id)->get()->toArray();

        return view('delivery_orders.show')->with('deliveryOrder', $deliveryOrder)->with('deliveryorderdetails', $deliveryorderdetails)->with('id', $id);
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        if (!empty($deliveryOrder->invoice_id)) {
            Flash::error('Delivery Order had already been converted to an invoice');

            return redirect(route('deliveryOrders.index'));
        }

        return view('delivery_orders.edit')->with('deliveryOrder', $deliveryOrder);
    }

    public function update($id, UpdateDeliveryOrderRequest $request)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        if (!empty($deliveryOrder->invoice_id)) {
            Flash::error('Delivery Order had already been converted to an invoice');

            return redirect(route('deliveryOrders.index'));
        }

        $input = $request->all();
        $input['date'] = date_create($input['date']);
        if (empty($input['dono'])) {
            $input['dono'] = $deliveryOrder->dono;
        }

        $deliveryOrder = $this->deliveryOrderRepository->update($input, $id);

        Flash::success('Delivery Order updated successfully.');

        if ($input['method'] == 1) {
            return redirect(route('deliveryOrders.index'));
        } else {
            return redirect(route('deliveryOrders.show', encrypt($deliveryOrder->id)));
        }
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        if (!empty($deliveryOrder->invoice_id)) {
            Flash::error('Delivery Order had already been converted to an invoice');

            return redirect(route('deliveryOrders.index'));
        }

        DeliveryOrderDetail::where('deliveryorder_id', $deliveryOrder->id)->delete();
        $this->deliveryOrderRepository->delete($id);

        Flash::success('Delivery Order deleted successfully.');

        return redirect(route('deliveryOrders.index'));
    }

    public function massdestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        $count = 0;

        foreach ($ids as $id) {
            $deliveryOrder = $this->deliveryOrderRepository->find($id);

            if (empty($deliveryOrder) || !empty($deliveryOrder->invoice_id)) {
                continue;
            }

            DeliveryOrderDetail::where('deliveryorder_id', $deliveryOrder->id)->delete();
            $count = $count + DeliveryOrder::destroy($id);
        }

        return $count;
    }

    public function getcustomer($id)
    {
        $customer = Customer::where('id', $id)->first();

        if (empty($customer)) {
            return response()->json(['status' => false, 'message' => 'Customer not found!']);
        }

        return response()->json(['status' => true, 'message' => 'Customer found!', 'data' => $customer]);
    }

    public function detail(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        if (!empty($deliveryOrder->invoice_id)) {
            Flash::error('Delivery Order had already been converted to an invoice');

            return redirect(route('deliveryOrders.show', encrypt($id)));
        }

        return view('delivery_orders.detail')->with('id', $id);
    }

    public function adddetail($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $input = $request->all();

        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $detail = new DeliveryOrderDetail();
        $detail->deliveryorder_id = $id;
        $detail->product_id = $input['product_id'];
        $detail->quantity = $input['quantity'];
        $detail->price = $input['price'];
        $detail->totalprice = $input['quantity'] * $input['price'];
        $detail->remark = $input['remark'] ?? null;
        $detail->save();

        Flash::success('Delivery Order Detail saved successfully.');

        return redirect(route('deliveryOrders.show', encrypt($id)));
    }

    public function deletedetail($id)
    {
        $id = Crypt::decrypt($id);
        $detail = DeliveryOrderDetail::where('id', $id)->first();

        if (empty($detail)) {
            Flash::error('Delivery Order Detail not found');

            return redirect()->back();
        }

        $deliveryOrderId = $detail->deliveryorder_id;
        $detail->delete();

        Flash::success('Delivery Order Detail deleted successfully.');

        return redirect(route('deliveryOrders.show', encrypt($deliveryOrderId)));
    }

    /**
     * Combine the selected Delivery Orders (must all belong to the same customer)
     * into ONE Invoice. Works for a single DO too (ids array of length 1).
     * DOs only exist for Credit-term DO customers, so the resulting Invoice is
     * always numbered via the Credit invoice running number.
     */
    public function combineConvert(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'Please select at least one delivery order.'], 422);
        }

        $deliveryOrders = DeliveryOrder::with('deliveryorderdetail')
            ->whereIn('id', $ids)
            ->whereNull('invoice_id')
            ->get();

        if ($deliveryOrders->isEmpty()) {
            return response()->json(['message' => 'Selected delivery order(s) are already converted or not found.'], 422);
        }

        $customerIds = $deliveryOrders->pluck('customer_id')->unique();

        if ($customerIds->count() > 1) {
            return response()->json(['message' => 'Selected delivery orders must all belong to the same customer.'], 422);
        }

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceFromDeliveryOrders($deliveryOrders, date('Y-m-d H:i:s'));

            DB::commit();

            return response()->json([
                'message' => $deliveryOrders->count() . ' delivery order(s) converted into invoice ' . $invoice->invoiceno . '.',
                'count' => $deliveryOrders->count(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['message' => 'Something went wrong. Please contact administrator.'], 500);
        }
    }

    /**
     * One invoice from one customer's delivery orders (caller holds the
     * transaction). Numbered in the series of the first DO's driver, with the
     * month of $date.
     */
    private function invoiceFromDeliveryOrders($deliveryOrders, string $date, ?string $remark = null): Invoice
    {
        $first = $deliveryOrders->first();

        $invoice = new Invoice();
        $invoice->invoiceno = Code::nextDocumentNumber('IV', $first->driver_id, false, $date);
        $invoice->date = $date;
        $invoice->customer_id = $first->customer_id;
        $invoice->driver_id = $first->driver_id;
        $invoice->kelindan_id = $first->kelindan_id;
        $invoice->agent_id = $first->agent_id;
        $invoice->supervisor_id = $first->supervisor_id;
        $invoice->paymentterm = $first->paymentterm;
        $invoice->status = 0;
        $invoice->remark = $remark ?? ($deliveryOrders->count() > 1
            ? 'Combined from ' . $deliveryOrders->count() . ' delivery order(s)'
            : $first->remark);
        $invoice->chequeno = $first->chequeno;
        $tripIds = $deliveryOrders->pluck('trip_id')->unique();
        $invoice->trip_id = $tripIds->count() === 1 ? $tripIds->first() : null;
        $invoice->save();

        foreach ($deliveryOrders as $deliveryOrder) {
            foreach ($deliveryOrder->deliveryorderdetail as $line) {
                $detail = new InvoiceDetail();
                $detail->invoice_id = $invoice->id;
                $detail->product_id = $line->product_id;
                $detail->deliveryorder_id = $deliveryOrder->id;
                $detail->quantity = $line->quantity;
                $detail->price = $line->price;
                $detail->totalprice = $line->totalprice;
                $detail->remark = $line->remark;
                $detail->save();
            }

            // Only the link: a full save also writes `date`, which this model
            // formats for display.
            DeliveryOrder::where('id', $deliveryOrder->id)->update(['invoice_id' => $invoice->id]);
        }

        return $invoice;
    }

    // ------------------------------------------------------------ billing convert

    /**
     * The billing period's dates: from/to as given, or the last full week
     * (Mon - Sun) / last full month.
     */
    private function billingPeriod(Request $request): array
    {
        $cycle = $request->input('cycle') === 'monthly' ? 'monthly' : 'weekly';
        $from = $request->input('from');
        $to = $request->input('to');
        if (!$from || !$to || strtotime($from) === false || strtotime($to) === false) {
            if ($cycle === 'monthly') {
                $from = date('Y-m-01', strtotime('first day of last month'));
                $to = date('Y-m-t', strtotime('first day of last month'));
            } else {
                $from = date('Y-m-d', strtotime('monday last week'));
                $to = date('Y-m-d', strtotime('sunday last week'));
            }
        }

        return [$cycle, date('Y-m-d', strtotime($from)), date('Y-m-d', strtotime($to))];
    }

    /**
     * Pending DOs (not converted, not cancelled) of every customer on this
     * billing cycle, dated on or before the end of the period - so DOs left
     * over from earlier periods are billed too. Grouped by customer.
     */
    private function pendingBillingDeliveryOrders(string $cycle, string $to, ?array $customerIds = null)
    {
        $query = DeliveryOrder::with('deliveryorderdetail', 'customer', 'driver')
            ->whereNull('invoice_id')
            ->where('status', '!=', 2)
            ->where('date', '<=', $to . ' 23:59:59')
            ->whereHas('customer', function ($q) use ($cycle) {
                $q->where('billing_cycle', $cycle);
            });
        if ($customerIds !== null) {
            $query->whereIn('customer_id', $customerIds);
        }

        return $query->orderBy('date')->orderBy('id')->get()->groupBy('customer_id');
    }

    /**
     * Drivers whose trip is open now. Their invoice numbers are being used
     * on the phone (also without signal), so billing waits for End Trip.
     */
    private function driversOnTrip($driverIds): array
    {
        $onTrip = [];
        foreach (collect($driverIds)->filter()->unique() as $driverId) {
            $trip = \App\Models\Trip::where('driver_id', $driverId)->orderBy('date', 'desc')->orderBy('id', 'desc')->first();
            if ($trip && (int) $trip->type === 1) {
                $onTrip[$driverId] = optional(\App\Models\Driver::find($driverId))->name ?? ('Driver ' . $driverId);
            }
        }

        return $onTrip;
    }

    /** Billing Convert, step 1: what would be converted. */
    public function billingPreview(Request $request)
    {
        [$cycle, $from, $to] = $this->billingPeriod($request);
        $groups = $this->pendingBillingDeliveryOrders($cycle, $to);

        $rows = [];
        foreach ($groups as $customerId => $dos) {
            $first = $dos->first();
            $rows[] = [
                'customer_id' => $customerId,
                'customer' => optional($first->customer)->company ?? '-',
                'driver' => optional($first->driver)->name ?? '-',
                'count' => $dos->count(),
                'first_date' => date('d-m-Y', strtotime($dos->first()->getRawOriginal('date'))),
                'last_date' => date('d-m-Y', strtotime($dos->last()->getRawOriginal('date'))),
                'total' => number_format($dos->sum(function ($do) { return $do->deliveryorderdetail->sum('totalprice'); }), 2),
            ];
        }
        usort($rows, function ($a, $b) { return strcasecmp($a['customer'], $b['customer']); });

        return response()->json([
            'cycle' => $cycle,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'drivers_on_trip' => array_values($this->driversOnTrip($groups->map(function ($dos) { return $dos->first()->driver_id; })->values())),
        ]);
    }

    /**
     * Billing Convert, step 2: one invoice per ticked customer from all their
     * pending DOs up to the end of the period, dated the last day of the
     * period. Refused while a driver whose number series is used is on an
     * open trip. Each customer is converted on its own, so one problem never
     * stops the rest.
     */
    public function billingConvert(Request $request)
    {
        [$cycle, $from, $to] = $this->billingPeriod($request);
        $customerIds = array_map('intval', (array) $request->input('customer_ids', []));
        if (empty($customerIds)) {
            return response()->json(['message' => 'Please tick at least one customer.'], 422);
        }

        $groups = $this->pendingBillingDeliveryOrders($cycle, $to, $customerIds);
        if ($groups->isEmpty()) {
            return response()->json(['message' => 'No pending delivery orders for the ticked customers.'], 422);
        }

        $onTrip = $this->driversOnTrip($groups->map(function ($dos) { return $dos->first()->driver_id; })->values());
        if ($onTrip) {
            return response()->json(['message' => 'Please convert after these drivers end their trip: ' . implode(', ', $onTrip) . '. Their invoice numbers are in use on the phone.'], 422);
        }

        $label = $cycle === 'monthly' ? 'Monthly billing' : 'Weekly billing';
        $remark = $label . ' ' . date('d-m-Y', strtotime($from)) . ' to ' . date('d-m-Y', strtotime($to));
        $invoiceDate = $to . ' 23:59:00';
        $done = [];
        $failed = [];
        foreach ($groups as $customerId => $dos) {
            DB::beginTransaction();
            try {
                // Re-read under lock: a DO converted meanwhile is left out.
                $fresh = DeliveryOrder::with('deliveryorderdetail')
                    ->whereIn('id', $dos->pluck('id'))
                    ->whereNull('invoice_id')
                    ->where('status', '!=', 2)
                    ->lockForUpdate()
                    ->orderBy('date')->orderBy('id')
                    ->get();
                if ($fresh->isEmpty()) {
                    DB::rollBack();
                    continue;
                }
                $invoice = $this->invoiceFromDeliveryOrders($fresh, $invoiceDate, $remark . ' (' . $fresh->count() . ' DO)');
                DB::commit();
                $done[] = [
                    'customer' => optional($dos->first()->customer)->company ?? '-',
                    'invoice_id' => $invoice->id,
                    'invoiceno' => $invoice->invoiceno,
                    'count' => $fresh->count(),
                    'url' => route('invoices.show', encrypt($invoice->id)),
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);
                $failed[] = ['customer' => optional($dos->first()->customer)->company ?? '-', 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'message' => count($done) . ' invoice(s) created' . ($failed ? ', ' . count($failed) . ' failed' : '') . '.',
            'invoices' => $done,
            'failed' => $failed,
        ]);
    }

    public function getprice($deliveryorder_id, $product_id)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryorder_id)->first();

        if (empty($deliveryOrder)) {
            return response()->json(['status' => false, 'message' => 'Delivery Order not found!']);
        }

        $product = Product::where('id', $product_id)->first();

        if (empty($product)) {
            return response()->json(['status' => false, 'message' => 'Product not found!']);
        }

        $price = $this->resolveCustomerPrice($product, $deliveryOrder->customer_id);

        return response()->json(['status' => true, 'message' => 'Price resolved.', 'data' => $price]);
    }

    /**
     * Price a product for a customer: the customer's AutoCount price category
     * selects the matching product_prices row; absent category, or no price for
     * it, falls back to the product's base price.
     */
    private function resolveCustomerPrice(Product $product, $customerId)
    {
        return Product::priceForCustomer($product->id, $product->price, $customerId);
    }

    public function getDoViewPDF($id, $function)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = DeliveryOrder::where('id', $id)
            ->with('customer')
            ->with('driver')
            ->with('deliveryorderdetail.product')
            ->first();

        if (empty($deliveryOrder)) {
            abort('404');
        }

        $min = 480;
        $each = 48;
        $height = (count($deliveryOrder['deliveryorderdetail']) * $each) + $min;
        if (!empty($deliveryOrder['remark'])) {
            $height += 40;
        }

        try {
            $pdf = Pdf::loadView('delivery_orders.print', ['deliveryOrder' => $deliveryOrder]);

            if ($function == 'download') {
                return $pdf->setPaper([0, 0, 300, $height], 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true], true)->download('download.pdf');
            } elseif ($function == 'view') {
                return $pdf->setPaper([0, 0, 300, $height], 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true], true)->stream('view.pdf');
            }
        } catch (Exception $e) {
            abort(404);
        }
    }
}
