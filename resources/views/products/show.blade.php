@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('products.index') }}">{{ __('products.products')}}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('products.detail')}}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex align-items-center">
                                 <a href="{{ route('products.index') }}" id="dv-back" class="btn btn-light btn-sm"><i class="fa fa-arrow-left"></i> {{ __('detail.back') }}</a>
                             </div>
                             <div class="card-body">
                                 @include('products.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
