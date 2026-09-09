@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Daily Sales Report</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-file-text-o"></i>
                            Daily Sales Report
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.daily-sales.pdf') }}" target="_blank">
                                @csrf
                                <div class="row">

                                    <div class="form-group col-sm-6">
                                        <label>Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control"
                                               value="{{ old('date_from', date('Y-m-01')) }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control"
                                               value="{{ old('date_to', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Customer <span class="text-muted small">(optional)</span></label>
                                        <select name="customer_id" class="form-control select2-customer">
                                            <option value="">— All Customers —</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}"
                                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->company }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Lorry <span class="text-muted small">(optional — leave blank for all lorries)</span></label>
                                        <select name="lorry_ids[]" class="form-control select2-lorry" multiple>
                                            @foreach($lorries as $lorry)
                                                <option value="{{ $lorry->id }}"
                                                    {{ in_array($lorry->id, old('lorry_ids', [])) ? 'selected' : '' }}>
                                                    {{ $lorry->lorryno }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Payment Type <span class="text-muted small">(optional)</span></label>
                                        <select name="payment_type" class="form-control">
                                            <option value="">— All Payment Types —</option>
                                            @foreach($paymentTerms as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ old('payment_type') == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

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
    $('.select2-customer').select2({ placeholder: 'Search customer...', allowClear: true, width: '100%' });
    $('.select2-lorry').select2({ placeholder: 'Search lorry...', allowClear: true, width: '100%' });
    HideLoad();
});
</script>
@endpush
