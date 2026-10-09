@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Payment Collection Report</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-money"></i>
                            Payment Collection Report
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.payment-collection.pdf') }}" target="_blank">
                                @csrf
                                <div class="row">

                                    <div class="form-group col-sm-6">
                                        <label>Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control"
                                               value="{{ old('date_from', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control"
                                               value="{{ old('date_to', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Driver</label>
                                        <select name="driver_id" class="form-control select2-driver">
                                            <option value="">All drivers</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}"
                                                    {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                                    {{ $driver->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <p class="text-muted small mb-3">
                                    Lists every payment collected in the period: which invoice it belongs to, who collected it,
                                    how it was paid and the amount. Leave both dates on today for today's collection.
                                </p>

                                <div class="form-group mt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </button>
                                    <a href="{{ route('reports.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </a>
                                </div>

                            </form>
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
    $('.select2-driver').select2({ placeholder: 'All drivers', allowClear: true, width: '100%' });
    HideLoad();
});
</script>
@endpush
