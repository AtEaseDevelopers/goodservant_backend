@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Commission</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-percent"></i>
                            Driver and Kelindan Commission
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.commission.show') }}">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <label>From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control"
                                               value="{{ old('date_from', $dateFrom) }}" required>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label>To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control"
                                               value="{{ old('date_to', $dateTo) }}" required>
                                    </div>
                                </div>
                                <div class="form-group mt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Show Report
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
    HideLoad();
});
</script>
@endpush
