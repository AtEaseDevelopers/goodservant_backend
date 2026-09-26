@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('apiLogs.index') }}">API Logs</a></li>
        <li class="breadcrumb-item">#{{ $apiLog->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            API Log #{{ $apiLog->id }}
                            <a href="{{ route('apiLogs.index') }}" class="pull-right"><i class="fa fa-arrow-left"></i> Back</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 180px;">Date</th>
                                    <td>{{ optional($apiLog->created_at)->timezone('+08:00')->format('d-m-Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Driver</th>
                                    <td>{{ optional($apiLog->driver)->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Method</th>
                                    <td>{{ $apiLog->method }}</td>
                                </tr>
                                <tr>
                                    <th>URL</th>
                                    <td>{{ $apiLog->url }}</td>
                                </tr>
                                <tr>
                                    <th>Status Code</th>
                                    <td>{{ $apiLog->status_code }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $apiLog->ip_address }}</td>
                                </tr>
                                <tr>
                                    <th>Headers</th>
                                    <td><pre style="white-space: pre-wrap;">{{ json_encode($apiLog->headers, JSON_PRETTY_PRINT) }}</pre></td>
                                </tr>
                                <tr>
                                    <th>Request Body</th>
                                    <td><pre style="white-space: pre-wrap;">{{ json_encode($apiLog->request_body, JSON_PRETTY_PRINT) }}</pre></td>
                                </tr>
                                <tr>
                                    <th>Response Body</th>
                                    <td><pre style="white-space: pre-wrap;">{{ json_encode($apiLog->response_body, JSON_PRETTY_PRINT) }}</pre></td>
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
