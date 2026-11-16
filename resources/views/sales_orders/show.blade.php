@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('salesOrders.index') }}">{{ __('sales_orders.sales_orders') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ $salesOrder->sono }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 @if($salesOrder->deliveryorder_id)
                 <div class="alert alert-info">
                     <i class="fa fa-exchange mr-1"></i> {{ __('sales_orders.converted_to_delivery_order') }}:
                     <a href="{{ route('deliveryOrders.show', encrypt($salesOrder->deliveryorder->id)) }}" class="font-weight-bold">{{ $salesOrder->deliveryorder->dono }}</a>
                 </div>
                 @elseif($salesOrder->invoice_id)
                 <div class="alert alert-info">
                     <i class="fa fa-exchange mr-1"></i> {{ __('sales_orders.converted_to_invoice') }}:
                     <a href="{{ route('invoices.show', encrypt($salesOrder->invoice->id)) }}" class="font-weight-bold">{{ $salesOrder->invoice->invoiceno }}</a>
                 </div>
                 @endif
                 <div class="card">
                     <div class="card-header d-flex align-items-center">
                         <a href="{{ route('salesOrders.index') }}" id="dv-back" class="btn btn-light btn-sm">
                             <i class="fa fa-arrow-left"></i> {{ __('detail.back') }}
                         </a>
                     </div>
                     <div class="card-body">
                         @include('sales_orders.show_fields', ['total' => collect($salesorderdetails)->sum('totalprice')])

                         @include('partials.order_items', [
                             'items' => $salesorderdetails,
                             'title' => __('sales_orders.sales_order_detail'),
                             'addUrl' => route('salesOrders.detail', Crypt::encrypt($id)),
                             'deleteRoute' => 'salesOrders.deletedetail',
                             'confirm' => __('detail.confirm_delete_item'),
                         ])
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") { $('#dv-back')[0].click(); }
            if (e.altKey && e.keyCode == 78) { $('#dv-add-item')[0].click(); }
        });
        $(document).ready(function () { HideLoad(); });
    </script>
@endpush
