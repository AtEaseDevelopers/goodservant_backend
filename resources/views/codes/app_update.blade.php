@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">App Update</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-mobile"></i>
                            Driver app update
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Phones on an older version than the latest version see an update banner on the
                                dashboard. Tapping Update opens the link for that phone type.
                            </p>
                            {!! Form::open(['route' => 'codes.appUpdate', 'method' => 'post']) !!}
                            <div class="form-group">
                                {!! Form::label('mobile_app_latest_version', 'Latest app version') !!}<span class="asterisk"> *</span>
                                {!! Form::text('mobile_app_latest_version', old('mobile_app_latest_version', $values['mobile_app_latest_version'] ?? ''), ['class' => 'form-control', 'placeholder' => '1.0.72', 'style' => 'max-width: 200px;']) !!}
                                <small class="form-text text-muted">
                                    For a Google Play / App Store release, change this only after the new version is
                                    live in the store, or drivers tap Update and still get the old one.
                                </small>
                            </div>
                            <div class="form-group">
                                {!! Form::label('mobile_app_update_url_android', 'Android update link') !!}
                                {!! Form::text('mobile_app_update_url_android', old('mobile_app_update_url_android', $values['mobile_app_update_url_android'] ?? ''), ['class' => 'form-control', 'placeholder' => 'https://play.google.com/store/apps/details?id=...']) !!}
                                <small class="form-text text-muted">
                                    The APK download link today; the Google Play link once the app is on Play.
                                    Phones installed from Google Play always go to Google Play.
                                </small>
                            </div>
                            <div class="form-group">
                                {!! Form::label('mobile_app_update_url_ios', 'iPhone update link') !!}
                                {!! Form::text('mobile_app_update_url_ios', old('mobile_app_update_url_ios', $values['mobile_app_update_url_ios'] ?? ''), ['class' => 'form-control', 'placeholder' => 'https://apps.apple.com/app/...']) !!}
                                <small class="form-text text-muted">The App Store link.</small>
                            </div>
                            {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
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
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
