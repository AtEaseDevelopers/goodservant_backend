@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('drivers.index') }}">{{ __('drivers.drivers') }}</a>
        </li>
        <li class="breadcrumb-item active">Task sequence</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            @include('coreui-templates::common.errors')
            @include('drivers.customer_sequence')
        </div>
    </div>
@endsection
