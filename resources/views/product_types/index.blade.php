@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ trans('side_menu.product_types') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ trans('side_menu.product_types') }}
                            <a class="pull-right" href="{{ route('productTypes.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ __('Manage the product type/category options drivers can filter by on the mobile Inventory screen - add one here and it\'s available everywhere immediately, no app update needed.') }}</p>
                            @include('product_types.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
