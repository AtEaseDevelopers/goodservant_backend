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
use App\Models\SpecialPrice;
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
            Code::where('code', 'dorunningnumber')->first()->increment('value');
            $input['dono'] = 'DO' . sprintf('%07d', Code::where('code', 'dorunningnumber')->first()->value);
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
            $first = $deliveryOrders->first();

            Code::where('code', 'invoicerunningnumber')->first()->increment('value');
            $invoiceno = 'INV' . sprintf('%07d', Code::where('code', 'invoicerunningnumber')->first()->value);

            $invoice = new Invoice();
            $invoice->invoiceno = $invoiceno;
            $invoice->date = $first->getRawOriginal('date');
            $invoice->customer_id = $first->customer_id;
            $invoice->driver_id = $first->driver_id;
            $invoice->kelindan_id = $first->kelindan_id;
            $invoice->agent_id = $first->agent_id;
            $invoice->supervisor_id = $first->supervisor_id;
            $invoice->paymentterm = $first->paymentterm;
            $invoice->status = 0;
            $invoice->remark = $deliveryOrders->count() > 1
                ? 'Combined from ' . $deliveryOrders->count() . ' delivery order(s)'
                : $first->remark;
            $invoice->chequeno = $first->chequeno;
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

                $deliveryOrder->invoice_id = $invoice->id;
                $deliveryOrder->save();
            }

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

        $specialprice = SpecialPrice::where('customer_id', $deliveryOrder->customer_id)->where('product_id', $product_id)->first();

        if (empty($specialprice)) {
            return response()->json(['status' => true, 'message' => 'Special Price not found!', 'data' => $product->price]);
        } else {
            return response()->json(['status' => true, 'message' => 'Special Price found!', 'data' => $specialprice->price]);
        }
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

        $min = 450;
        $each = 23;
        $height = (count($deliveryOrder['deliveryorderdetail']) * $each) + $min;

        try {
            $pdf = Pdf::loadView('delivery_orders.print', ['deliveryOrder' => $deliveryOrder]);

            if ($function == 'download') {
                return $pdf->setPaper([0, 0, 300, $height], 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->download('download.pdf');
            } elseif ($function == 'view') {
                return $pdf->setPaper([0, 0, 300, $height], 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->stream('view.pdf');
            }
        } catch (Exception $e) {
            abort(404);
        }
    }
}
