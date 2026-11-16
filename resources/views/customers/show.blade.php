@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('customers.index') }}">{{ __('customers.customers')}}</a>
            </li>
            <li class="breadcrumb-item active">{{ $customer->company ?: __('customers.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="card">
                     <div class="card-header d-flex align-items-center">
                         <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm mr-2">
                             <i class="fa fa-arrow-left"></i> {{ __('customers.back') }}
                         </a>
                         <a href="{{ route('customers.edit', \Crypt::encrypt($customer->id)) }}" class="btn btn-outline-primary btn-sm">
                             <i class="fa fa-edit"></i> {{ __('customers.edit') }}
                         </a>
                         <a href="{{ route('customers.portal-login', \Crypt::encrypt($customer->id)) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm ml-auto">
                             <i class="fa fa-external-link"></i> {{ __('customers.open_customer_portal') }}
                         </a>
                     </div>
                     <div class="card-body">
                         @include('customers.show_fields')
                     </div>
                 </div>
          </div>
    </div>
@endsection
