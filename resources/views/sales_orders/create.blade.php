@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('salesOrders.index') !!}">{{ __('sales_orders.sales_orders') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('sales_orders.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('sales_orders.create_sales_order') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'salesOrders.store']) !!}

                                   @include('sales_orders.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
