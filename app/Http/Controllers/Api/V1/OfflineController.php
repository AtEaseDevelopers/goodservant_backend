<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\PaymentAttachment;
use App\Support\OfflineNumber;
use App\Support\ReceiptTemplate;
use Illuminate\Http\Request;

/**
 * What the driver app needs to work without signal.
 */
class OfflineController extends Controller
{
    private $message_separator = '|';

    /**
     * Downloaded by the app after login and whenever it is online:
     *  - server time, so the app can refuse offline work on a wrong clock
     *  - the driver's document code and last used offline numbers, so the
     *    phone's A-number counter never goes back (reinstall, new phone)
     *  - the receipt layouts and company header, so an offline receipt is
     *    drawn exactly like the server's
     *  - whether this driver still has documents waiting on another phone
     */
    public function setup(Request $request)
    {
        $driver = Driver::where('session', $request->header('session'))->first();
        if (empty($driver)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null,
            ], 401);
        }

        $receipts = [];
        foreach (ReceiptTemplate::TYPES as $type) {
            $receipts[$type] = ReceiptTemplate::load($type);
        }

        $device = (string) $request->header('Device-Id', '');
        $elsewhere = ($driver->offline_pending > 0 && $driver->offline_device && $driver->offline_device !== $device)
            ? (int) $driver->offline_pending
            : 0;

        return response()->json([
            'result' => true,
            'message' => __LINE__ . $this->message_separator . 'api.message.success',
            'data' => [
                'server_time' => date('Y-m-d H:i:s'),
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'doc_code' => $driver->documentCode(),
                'last_numbers' => (object) OfflineNumber::lastUsedMap($driver),
                'receipt_version' => ReceiptTemplate::version(),
                'receipts' => $receipts,
                'company' => ReceiptTemplate::company(),
                'logo_url' => url('receipts_logo.png'),
                'payment_types' => ReceiptTemplate::PAYMENT_TYPES,
                'pending_on_other_phone' => $elsewhere,
                'price_book' => $this->priceBook(),
            ],
        ], 200);
    }

    /**
     * Everything the phone needs to price any customer without signal, with
     * the same rule as Product::priceForCustomer(): an active special price
     * above zero, else the customer's price category above zero, else the
     * product's own price. The products themselves (names, pictures, lorry
     * stock) come from the normal product list the app already keeps.
     */
    private function priceBook(): array
    {
        $special = [];
        foreach (\App\Models\SpecialPrice::where('status', 1)->where('price', '>', 0)->get(['customer_id', 'product_id', 'price']) as $row) {
            $special[$row->customer_id][$row->product_id] = (float) $row->price;
        }
        $category = [];
        foreach (\App\Models\ProductPrice::where('price', '>', 0)->get(['product_price_category_id', 'product_id', 'price']) as $row) {
            $category[$row->product_price_category_id][$row->product_id] = (float) $row->price;
        }
        $base = \App\Models\Product::where('status', 1)->pluck('price', 'id')->map(function ($p) {
            return (float) $p;
        });

        return [
            'base' => (object) $base->all(),
            'special' => (object) $special,
            'category' => (object) $category,
            'customer_category' => (object) \App\Models\Customer::whereNotNull('product_price_category_id')
                ->pluck('product_price_category_id', 'id')->all(),
        ];
    }

    /**
     * Payment photos taken with an offline invoice, sent once the invoice
     * itself has uploaded. Found by the same request id (client_ref).
     * Sending the same photos again is harmless: each upload is kept once
     * per file name.
     */
    public function attachments(Request $request)
    {
        $driver = Driver::where('session', $request->header('session'))->first();
        if (empty($driver)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null,
            ], 401);
        }

        $ref = (string) $request->input('client_ref', '');
        $document = $ref === '' ? null
            : (Invoice::where('client_ref', $ref)->where('driver_id', $driver->id)->first()
                ?? DeliveryOrder::where('client_ref', $ref)->where('driver_id', $driver->id)->first());
        if (!$document) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invoice_not_found',
                'data' => null,
            ], 400);
        }

        $saved = 0;
        foreach ((array) $request->file('attachments', []) as $file) {
            if (!$file || !$file->isValid() || strpos((string) $file->getMimeType(), 'image/') !== 0) {
                continue;
            }
            // The app names each photo after the document and its position,
            // so a resent upload is recognised and not stored twice.
            $name = 'offline-' . preg_replace('/[^A-Za-z0-9_.-]/', '', $ref) . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '', $file->getClientOriginalName());
            $path = 'payment-attachments/' . $name;
            $exists = PaymentAttachment::where('attachable_type', get_class($document))
                ->where('attachable_id', $document->id)
                ->where('file_path', $path)
                ->exists();
            if ($exists) {
                continue;
            }
            $file->storeAs('payment-attachments', $name, 'public');
            PaymentAttachment::create([
                'attachable_type' => get_class($document),
                'attachable_id' => $document->id,
                'file_path' => $path,
            ]);
            $saved++;
        }

        return response()->json([
            'result' => true,
            'message' => __LINE__ . $this->message_separator . 'api.message.success',
            'data' => ['saved' => $saved],
        ], 200);
    }
}
