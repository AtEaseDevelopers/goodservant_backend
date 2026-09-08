@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('salesOrders.index') }}">{{ __('sales_orders.sales_orders') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('sales_orders.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 @if($salesOrder->deliveryorder_id)
                 <div class="alert alert-info">
                     {{ __('sales_orders.converted_to_delivery_order') }}: <a href="{{ route('deliveryOrders.show', encrypt($salesOrder->deliveryorder->id)) }}">{{ $salesOrder->deliveryorder->dono }}</a>
                 </div>
                 @elseif($salesOrder->invoice_id)
                 <div class="alert alert-info">
                     {{ __('sales_orders.converted_to_invoice') }}: <a href="{{ route('invoices.show', encrypt($salesOrder->invoice->id)) }}">{{ $salesOrder->invoice->invoiceno }}</a>
                 </div>
                 @endif
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                                  <a href="{{ route('salesOrders.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('sales_orders.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('sales_orders.sales_order_detail') }}</strong>
                                <a class="pull-right" href="{{ route('salesOrders.detail', Crypt::encrypt($id)) }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             </div>
                             <div class="card-body">
                                <table class="table table-striped table-bordered dataTable" width="100%" role="grid" style="width: 100%;">
                                    <thead>
                                        <tr role="row">
                                            <th>{{ __('sales_orders.product') }}</th>
                                            <th>{{ __('sales_orders.quantity') }}</th>
                                            <th>{{ __('sales_orders.price') }}</th>
                                            <th>{{ __('sales_orders.total_price') }}</th>
                                            <th>{{ __('sales_orders.remark') }}</th>
                                            <th>{{ __('sales_orders.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($salesorderdetails) == 0)
                                            <tr class="odd">
                                                <td valign="top" colspan="10" class="dataTables_empty">No matching records found</td>
                                            </tr>
                                        @endif
                                        @foreach($salesorderdetails as $i=>$salesorderdetail)
                                            @if( ($i+1) % 2 == 0 )

                                                <tr class="even">
                                                    <td>{{ $salesorderdetail['product']['name'] }}</td>
                                                    <td>{{ $salesorderdetail['quantity'] }}</td>
                                                    <td>{{ $salesorderdetail['price'] }}</td>
                                                    <td>{{ $salesorderdetail['totalprice'] }}</td>
                                                    <td>{{ $salesorderdetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['salesOrders.deletedetail', Crypt::encrypt($salesorderdetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Sales Order Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr class="odd">
                                                    <td>{{ $salesorderdetail['product']['name'] }}</td>
                                                    <td>{{ $salesorderdetail['quantity'] }}</td>
                                                    <td>{{ $salesorderdetail['price'] }}</td>
                                                    <td>{{ $salesorderdetail['totalprice'] }}</td>
                                                    <td>{{ $salesorderdetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['salesOrders.deletedetail', Crypt::encrypt($salesorderdetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Sales Order Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>

                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[1].click();
            }
        });
    </script>
@endpush
