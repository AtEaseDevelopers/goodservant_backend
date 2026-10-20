@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.commission') }}">Commission</a></li>
        <li class="breadcrumb-item active">{{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Driver Commission</strong>
                            <span class="text-muted">{{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</span>
                            <a href="{{ route('reports.commission') }}" class="btn btn-light btn-sm float-right">Change dates</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Driver</th>
                                        <th>Code</th>
                                        <th class="text-right">Rate (%)</th>
                                        <th class="text-right">Sales (RM)</th>
                                        <th class="text-right">Commission (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($drivers as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td>{{ $row['code'] }}</td>
                                        <td class="text-right">{{ number_format($row['rate'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['sales'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['commission'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center">No drivers.</td></tr>
                                    @endforelse
                                    <tr class="font-weight-bold">
                                        <td colspan="3">TOTAL</td>
                                        <td class="text-right">{{ number_format($drivers->sum('sales'), 2) }}</td>
                                        <td class="text-right">{{ number_format($drivers->sum('commission'), 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Kelindan Commission</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kelindan</th>
                                        <th class="text-right">Rate (%)</th>
                                        <th class="text-right">Sales (RM)</th>
                                        <th class="text-right">Commission (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kelindans as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="text-right">{{ number_format($row['rate'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['sales'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['commission'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center">No kelindans.</td></tr>
                                    @endforelse
                                    <tr class="font-weight-bold">
                                        <td colspan="2">TOTAL</td>
                                        <td class="text-right">{{ number_format($kelindans->sum('sales'), 2) }}</td>
                                        <td class="text-right">{{ number_format($kelindans->sum('commission'), 2) }}</td>
                                    </tr>
                                </tbody>
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
