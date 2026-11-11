@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('customers.index') }}">{{ __('customers.customers')}}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('customers.detail')}}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('customers.detail')}}</strong>
                                  <a href="{{ route('customers.index') }}" class="btn btn-light">Back</a>
                                  <a href="{{ route('customers.portal-login', \Crypt::encrypt($customer->id)) }}" target="_blank" rel="noopener" class="btn btn-primary float-right">
                                      <i class="fa fa-external-link"></i> Open Customer Portal
                                  </a>
                             </div>
                             <div class="card-body">
                                 @include('customers.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
