@extends('layouts.app')

@section('content')
    <ol class="breadcrumb d-print-none">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Inventory Balance</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-cubes"></i>
                            Inventory Balance after End Trip
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('reports.inventory-balance') }}" class="d-print-none">
                                <div class="row">
                                    <div class="form-group col-sm-4">
                                        <label>Driver</label>
                                        <select name="driver_id" class="form-control">
                                            <option value="">All drivers</option>
                                            @foreach($drivers as $id => $name)
                                                <option value="{{ $id }}" {{ (string) $driverId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label>Trip ended from</label>
                                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" required>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label>Trip ended to</label>
                                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
                                    </div>
                                    <div class="form-group col-sm-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mb-3 mr-2">
                                            <i class="fa fa-search"></i> Show
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary mb-3" onclick="window.print()">
                                            <i class="fa fa-print"></i> Print
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <p class="d-none d-print-block">
                                {{ $driverId ? ($drivers[$driverId] ?? '') : 'All drivers' }},
                                trips ended {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}
                            </p>

                            @if($trips->isEmpty())
                                <p class="text-muted">No trips ended in this date range.</p>
                            @else
                                <p class="text-muted d-print-none">
                                    The stock left on the lorry at the moment the driver ended each trip, newest first.
                                    Products with nothing left are not listed. A minus quantity means the driver sold or wasted more than the lorry had on record.
                                </p>
                                @foreach($trips as $trip)
                                    <div class="border rounded p-3 mb-3" style="page-break-inside: avoid;">
                                        <div class="d-flex flex-wrap justify-content-between mb-2">
                                            <div>
                                                <strong>{{ $trip->driver_name }}</strong>
                                                <span class="text-muted">&middot; {{ $trip->lorry_no }}</span>
                                            </div>
                                            <div class="text-muted">
                                                Trip ended {{ $trip->ended_at->format('d-m-Y h:i A') }}
                                                &middot; <strong class="{{ $trip->total == 0 ? '' : 'text-dark' }}">{{ $trip->total }}</strong> left
                                            </div>
                                        </div>
                                        @if($trip->lines->isEmpty())
                                            <p class="text-success mb-0"><i class="fa fa-check"></i> No stock left.</p>
                                        @else
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20%;">Code</th>
                                                        <th>Product</th>
                                                        <th class="text-right" style="width: 15%;">Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($trip->lines as $line)
                                                        <tr>
                                                            <td>{{ $line->code }}</td>
                                                            <td>{{ $line->name }}</td>
                                                            <td class="text-right {{ $line->quantity < 0 ? 'text-danger' : '' }}">{{ $line->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            <a href="{{ route('reports.index') }}" class="btn btn-secondary d-print-none">Back</a>
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
