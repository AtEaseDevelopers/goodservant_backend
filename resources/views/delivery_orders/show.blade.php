@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('deliveryOrders.index') }}">{{ __('delivery_orders.delivery_orders') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ $deliveryOrder->dono }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')

                 @if($deliveryOrder->invoice_id)
                 <div class="alert alert-info">
                    <i class="fa fa-exchange mr-1"></i> {{ __('delivery_orders.converted_to_invoice') }}:
                    <a href="{{ route('invoices.show', encrypt($deliveryOrder->invoice->id)) }}" class="font-weight-bold">{{ $deliveryOrder->invoice->invoiceno }}</a>
                 </div>
                 @endif

                 @if($deliveryOrder->salesorder)
                 <div class="alert alert-secondary">
                    <i class="fa fa-level-up mr-1"></i> {{ __('delivery_orders.originated_from_sales_order') }}:
                    <a href="{{ route('salesOrders.show', encrypt($deliveryOrder->salesorder->id)) }}" class="font-weight-bold">{{ $deliveryOrder->salesorder->sono }}</a>
                 </div>
                 @endif

                 <div class="card">
                     <div class="card-header d-flex align-items-center">
                         <a href="{{ route('deliveryOrders.index') }}" id="dv-back" class="btn btn-light btn-sm">
                             <i class="fa fa-arrow-left"></i> {{ __('detail.back') }}
                         </a>
                     </div>
                     <div class="card-body">
                         @include('delivery_orders.show_fields', ['total' => collect($deliveryorderdetails)->sum('totalprice')])

                         @include('partials.order_items', [
                             'items' => $deliveryorderdetails,
                             'title' => __('delivery_orders.delivery_order_detail'),
                             'addUrl' => route('deliveryOrders.detail', Crypt::encrypt($id)),
                             'deleteRoute' => 'deliveryOrders.deletedetail',
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
