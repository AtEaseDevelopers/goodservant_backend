@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('API Logs') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('API Logs') }}
                            {!! Form::open(['route' => 'apiLogs.clear', 'method' => 'delete', 'class' => 'pull-right']) !!}
                            {!! Form::button('<i class="fa fa-trash"></i> ' . e(__('Clear older than 7 days')), [
                                'type' => 'submit',
                                'class' => 'btn btn-sm btn-outline-danger',
                                'onclick' => "return confirm('Delete all log entries older than 7 days?')"
                            ]) !!}
                            {!! Form::close() !!}
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ __('Every mobile API request/response, for checking backend bugs without needing direct database access.') }}</p>
                            @include('api_logs.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
