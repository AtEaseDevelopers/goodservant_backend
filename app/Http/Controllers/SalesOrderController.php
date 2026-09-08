<?php

namespace App\Http\Controllers;

use App\DataTables\SalesOrderDataTable;
use App\Http\Requests\CreateSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Repositories\SalesOrderRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
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

class SalesOrderController extends AppBaseController
{
    /** @var SalesOrderRepository $salesOrderRepository*/
    private $salesOrderRepository;

    public function __construct(SalesOrderRepository $salesOrderRepo)
    {
        $this->salesOrderRepository = $salesOrderRepo;
    }

    /**
     * Payment term codes shared with Invoice: 1=Cash, 2=Credit, 3=Online BankIn, 4=E-wallet, 5=Cheque
     */
    const PAYMENTTERM_CREDIT = 2;

    public function index(Request $request, SalesOrderDataTable $salesOrderDataTable)
    {
        return $salesOrderDataTable->render('sales_orders.index');
    }

    public function create()
    {
        return view('sales_orders.create');
    }

    public function store(CreateSalesOrderRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if (empty($input['sono'])) {
            Code::where('code', 'sorunningnumber')->first()->increment('value');
            $input['sono'] = 'SO' . sprintf('%07d', Code::where('code', 'sorunningnumber')->first()->value);
        }

        $salesOrder = $this->salesOrderRepository->create($input);

        Flash::success('Sales Order saved successfully.');

        if ($input['method'] == 1) {
            return redirect(route('salesOrders.index'));
        } else {
            return redirect(route('salesOrders.show', encrypt($salesOrder->id)));
        }
    }

    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        $salesorderdetails = SalesOrderDetail::with('product')->where('sales_order_id', $id)->get()->toArray();

        return view('sales_orders.show')->with('salesOrder', $salesOrder)->with('salesorderdetails', $salesorderdetails)->with('id', $id);
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        if (!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
            Flash::error('Sales Order had already been converted');

            return redirect(route('salesOrders.index'));
        }

        return view('sales_orders.edit')->with('salesOrder', $salesOrder);
    }

    public function update($id, UpdateSalesOrderRequest $request)
    {
        $id = Crypt::decrypt($id);
        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        if (!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
            Flash::error('Sales Order had already been converted');

            return redirect(route('salesOrders.index'));
        }

        $input = $request->all();
        $input['date'] = date_create($input['date']);
        if (empty($input['sono'])) {
            $input['sono'] = $salesOrder->sono;
        }

        $salesOrder = $this->salesOrderRepository->update($input, $id);

        Flash::success('Sales Order updated successfully.');

        if ($input['method'] == 1) {
            return redirect(route('salesOrders.index'));
        } else {
            return redirect(route('salesOrders.show', encrypt($salesOrder->id)));
        }
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        if (!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
            Flash::error('Sales Order had already been converted, cannot be deleted');

            return redirect(route('salesOrders.index'));
        }

        SalesOrderDetail::where('sales_order_id', $salesOrder->id)->delete();
        $this->salesOrderRepository->delete($id);

        Flash::success('Sales Order deleted successfully.');

        return redirect(route('salesOrders.index'));
    }

    public function massdestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        $count = 0;

        foreach ($ids as $id) {
            $salesOrder = $this->salesOrderRepository->find($id);

            if (empty($salesOrder) || !empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
                continue;
            }

            SalesOrderDetail::where('sales_order_id', $salesOrder->id)->delete();
            $count = $count + SalesOrder::destroy($id);
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
        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        if (!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
            Flash::error('Sales Order had already been converted');

            return redirect(route('salesOrders.show', encrypt($id)));
        }

        return view('sales_orders.detail')->with('id', $id);
    }

    public function adddetail($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $input = $request->all();

        $salesOrder = $this->salesOrderRepository->find($id);

        if (empty($salesOrder)) {
            Flash::error('Sales Order not found');

            return redirect(route('salesOrders.index'));
        }

        $detail = new SalesOrderDetail();
        $detail->sales_order_id = $id;
        $detail->product_id = $input['product_id'];
        $detail->quantity = $input['quantity'];
        $detail->price = $input['price'];
        $detail->totalprice = $input['quantity'] * $input['price'];
        $detail->remark = $input['remark'] ?? null;
        $detail->save();

        Flash::success('Sales Order Detail saved successfully.');

        return redirect(route('salesOrders.show', encrypt($id)));
    }

    public function deletedetail($id)
    {
        $id = Crypt::decrypt($id);
        $detail = SalesOrderDetail::where('id', $id)->first();

        if (empty($detail)) {
            Flash::error('Sales Order Detail not found');

            return redirect()->back();
        }

        $salesOrderId = $detail->sales_order_id;
        $detail->delete();

        Flash::success('Sales Order Detail deleted successfully.');

        return redirect(route('salesOrders.show', encrypt($salesOrderId)));
    }

    /**
     * Convert one Sales Order into either a Delivery Order (if the customer is
     * DO-configured and Credit terms are chosen) or an Invoice (otherwise).
     * Payment method is chosen here, at conversion time.
     */
    public function convert(Request $request)
    {
        $id = $request->input('id');
        $paymentterm = (int) $request->input('paymentterm');
        $chequeno = $request->input('chequeno');

        $salesOrder = SalesOrder::with('salesorderdetail')->find($id);

        if (empty($salesOrder)) {
            return response()->json(['message' => 'Sales Order not found.'], 422);
        }

        if (!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)) {
            return response()->json(['message' => 'Sales Order had already been converted.'], 422);
        }

        if ($salesOrder->salesorderdetail->isEmpty()) {
            return response()->json(['message' => 'Sales Order has no items to convert.'], 422);
        }

        if (empty($paymentterm)) {
            return response()->json(['message' => 'Please choose a payment method.'], 422);
        }

        $customer = Customer::find($salesOrder->customer_id);

        if (empty($customer)) {
            return response()->json(['message' => 'Customer not found.'], 422);
        }

        DB::beginTransaction();
        try {
            if ($customer->is_do_customer && $paymentterm == self::PAYMENTTERM_CREDIT) {
                $result = $this->convertToDeliveryOrder($salesOrder, $paymentterm, $chequeno);
                $message = 'Sales Order converted to Delivery Order ' . $result->dono . '.';
            } else {
                $result = $this->convertToInvoice($salesOrder, $paymentterm, $chequeno);
                $message = 'Sales Order converted to Invoice ' . $result->invoiceno . '.';
            }

            DB::commit();

            return response()->json(['message' => $message]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['message' => 'Something went wrong. Please contact administrator.'], 500);
        }
    }

    private function convertToDeliveryOrder(SalesOrder $salesOrder, $paymentterm, $chequeno)
    {
        Code::where('code', 'dorunningnumber')->first()->increment('value');
        $dono = 'DO' . sprintf('%07d', Code::where('code', 'dorunningnumber')->first()->value);

        $deliveryOrder = new DeliveryOrder();
        $deliveryOrder->dono = $dono;
        $deliveryOrder->date = $salesOrder->getRawOriginal('date');
        $deliveryOrder->customer_id = $salesOrder->customer_id;
        $deliveryOrder->driver_id = $salesOrder->driver_id;
        $deliveryOrder->kelindan_id = $salesOrder->kelindan_id;
        $deliveryOrder->agent_id = $salesOrder->agent_id;
        $deliveryOrder->supervisor_id = $salesOrder->supervisor_id;
        $deliveryOrder->paymentterm = $paymentterm;
        $deliveryOrder->status = 0;
        $deliveryOrder->remark = $salesOrder->remark;
        $deliveryOrder->chequeno = $chequeno;
        $deliveryOrder->save();

        foreach ($salesOrder->salesorderdetail as $line) {
            $detail = new DeliveryOrderDetail();
            $detail->deliveryorder_id = $deliveryOrder->id;
            $detail->product_id = $line->product_id;
            $detail->quantity = $line->quantity;
            $detail->price = $line->price;
            $detail->totalprice = $line->totalprice;
            $detail->remark = $line->remark;
            $detail->save();
        }

        $salesOrder->deliveryorder_id = $deliveryOrder->id;
        $salesOrder->save();

        return $deliveryOrder;
    }

    private function convertToInvoice(SalesOrder $salesOrder, $paymentterm, $chequeno)
    {
        if ($paymentterm == self::PAYMENTTERM_CREDIT) {
            Code::where('code', 'invoicerunningnumber')->first()->increment('value');
            $invoiceno = 'INV' . sprintf('%07d', Code::where('code', 'invoicerunningnumber')->first()->value);
        } else {
            Code::where('code', 'cashsalesrunningnumber')->first()->increment('value');
            $invoiceno = 'CS' . sprintf('%07d', Code::where('code', 'cashsalesrunningnumber')->first()->value);
        }

        $invoice = new Invoice();
        $invoice->invoiceno = $invoiceno;
        $invoice->date = $salesOrder->getRawOriginal('date');
        $invoice->customer_id = $salesOrder->customer_id;
        $invoice->driver_id = $salesOrder->driver_id;
        $invoice->kelindan_id = $salesOrder->kelindan_id;
        $invoice->agent_id = $salesOrder->agent_id;
        $invoice->supervisor_id = $salesOrder->supervisor_id;
        $invoice->paymentterm = $paymentterm;
        $invoice->status = 0;
        $invoice->remark = $salesOrder->remark;
        $invoice->chequeno = $chequeno;
        $invoice->save();

        foreach ($salesOrder->salesorderdetail as $line) {
            $detail = new InvoiceDetail();
            $detail->invoice_id = $invoice->id;
            $detail->product_id = $line->product_id;
            $detail->sales_order_id = $salesOrder->id;
            $detail->quantity = $line->quantity;
            $detail->price = $line->price;
            $detail->totalprice = $line->totalprice;
            $detail->remark = $line->remark;
            $detail->save();
        }

        $salesOrder->invoice_id = $invoice->id;
        $salesOrder->save();

        return $invoice;
    }

    public function getprice($salesorder_id, $product_id)
    {
        $salesOrder = SalesOrder::where('id', $salesorder_id)->first();

        if (empty($salesOrder)) {
            return response()->json(['status' => false, 'message' => 'Sales Order not found!']);
        }

        $product = Product::where('id', $product_id)->first();

        if (empty($product)) {
            return response()->json(['status' => false, 'message' => 'Product not found!']);
        }

        $specialprice = SpecialPrice::where('customer_id', $salesOrder->customer_id)->where('product_id', $product_id)->first();

        if (empty($specialprice)) {
            return response()->json(['status' => true, 'message' => 'Special Price not found!', 'data' => $product->price]);
        } else {
            return response()->json(['status' => true, 'message' => 'Special Price found!', 'data' => $specialprice->price]);
        }
    }

    public function getSoViewPDF($id, $function)
    {
        $id = Crypt::decrypt($id);
        $salesOrder = SalesOrder::where('id', $id)
            ->with('customer')
            ->with('driver')
            ->with('salesorderdetail.product')
            ->first();

        if (empty($salesOrder)) {
            abort('404');
        }

        $min = 450;
        $each = 23;
        $height = (count($salesOrder['salesorderdetail']) * $each) + $min;

        try {
            $pdf = Pdf::loadView('sales_orders.print', ['salesOrder' => $salesOrder]);

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
