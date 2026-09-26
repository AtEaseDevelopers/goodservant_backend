@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('mobileErrorLogs.index') }}">Mobile App Error Logs</a></li>
        <li class="breadcrumb-item">#{{ $mobileErrorLog->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Error Log #{{ $mobileErrorLog->id }}
                            <a href="{{ route('mobileErrorLogs.index') }}" class="pull-right"><i class="fa fa-arrow-left"></i> Back</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 180px;">Date</th>
                                    <td>{{ optional($mobileErrorLog->created_at)->timezone('+08:00')->format('d-m-Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Driver</th>
                                    <td>{{ optional($mobileErrorLog->driver)->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>App Version</th>
                                    <td>{{ $mobileErrorLog->app_version ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Screen</th>
                                    <td>{{ $mobileErrorLog->screen ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Message</th>
                                    <td><pre style="white-space: pre-wrap;">{{ $mobileErrorLog->message ?? '-' }}</pre></td>
                                </tr>
                                <tr>
                                    <th>Stack Trace</th>
                                    <td><pre style="white-space: pre-wrap;">{{ $mobileErrorLog->stack_trace ?? '-' }}</pre></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
