@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoicePayments.index') }}">{{ __('invoice_payments.invoice_payments') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoice_payments.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex align-items-center">
                                 <a href="{{ route('invoicePayments.index') }}" id="dv-back" class="btn btn-light btn-sm"><i class="fa fa-arrow-left"></i> {{ __('detail.back') }}</a>
                             </div>
                             <div class="card-body">
                                 @include('invoice_payments.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
