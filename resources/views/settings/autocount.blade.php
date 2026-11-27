@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('invoices.index') !!}">{{ trans('side_menu.invoices') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('AutoCount Sync Settings') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-cog fa-lg"></i>
                            <strong>{{ __('AutoCount Sync Settings') }}</strong>
                        </div>
                        <div class="card-body">
                            {!! Form::open(['route' => 'settings.autocount.update', 'method' => 'post']) !!}

                            <p class="text-muted">
                                Each night at the time below, every invoice that has not yet synced
                                to AutoCount (not synced, or previously failed) is queued so the
                                AutoCount plugin picks it up on its next poll. This is a safety net
                                on top of per-customer auto-sync.
                            </p>

                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('nightly_sync_enabled', 1, $nightlySyncEnabled) !!}
                                        Enable nightly sync of all unsynced invoices
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('nightly_sync_time', 'Nightly sync time (24h, Asia/Kuala_Lumpur)') !!}
                                {!! Form::time('nightly_sync_time', $nightlySyncTime, ['class' => 'form-control', 'required' => true]) !!}
                                <small class="form-text text-muted">e.g. 23:00 for 11:00 PM.</small>
                            </div>

                            <div class="form-group">
                                {!! Form::submit(__('Save'), ['class' => 'btn btn-primary']) !!}
                                <a href="{!! route('invoices.index') !!}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>

                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // The layout shows a full-screen #loading overlay until a page dismisses
        // it; without this call the spinner would stay up forever on this page.
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
