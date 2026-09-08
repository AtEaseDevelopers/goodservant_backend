@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('salesOrders.index') !!}">{{ __('sales_orders.sales_orders') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('sales_orders.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('sales_orders.edit_sales_order') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($salesOrder, ['route' => ['salesOrders.update', encrypt($salesOrder->id)], 'method' => 'patch']) !!}

                              @include('sales_orders.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection
