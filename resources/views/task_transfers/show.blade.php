@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('taskTransfers.index') }}">Task Transfer</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex align-items-center">
                                 <a href="{{ route('taskTransfers.index') }}" id="dv-back" class="btn btn-light btn-sm"><i class="fa fa-arrow-left"></i> {{ __('detail.back') }}</a>
                             </div>
                             <div class="card-body">
                                 @include('task_transfers.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
