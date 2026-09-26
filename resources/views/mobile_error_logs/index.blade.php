@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Mobile App Error Logs</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Mobile App Error Logs
                            {!! Form::open(['route' => 'mobileErrorLogs.clear', 'method' => 'delete', 'class' => 'pull-right']) !!}
                            {!! Form::button('<i class="fa fa-trash"></i> Clear all', [
                                'type' => 'submit',
                                'class' => 'btn btn-sm btn-outline-danger',
                                'onclick' => "return confirm('Delete all logged mobile errors?')"
                            ]) !!}
                            {!! Form::close() !!}
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Automatically captured crashes/errors from the driver mobile app - use this to see what went wrong without needing to reproduce it yourself.</p>
                            @include('mobile_error_logs.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
