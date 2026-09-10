@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Packing List</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-truck"></i>
                            Packing List
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.packing-list.pdf') }}" target="_blank">
                                @csrf
                                <div class="row">

                                    <div class="form-group col-sm-6">
                                        <label>Driver <span class="text-danger">*</span></label>
                                        <select name="driver_id" class="form-control select2-driver" required>
                                            <option value="">Select a driver...</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}"
                                                    {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                                    {{ $driver->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label>Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" class="form-control"
                                               value="{{ old('date', date('Y-m-d')) }}" required>
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
    $('.select2-driver').select2({ placeholder: 'Search driver...', allowClear: true, width: '100%' });
    HideLoad();
});
</script>
@endpush
