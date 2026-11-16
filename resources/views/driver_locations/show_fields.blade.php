@include('partials.detail_styles')
@php
    $l = $driverLocation;
    $hasPoint = is_numeric($l->latitude) && is_numeric($l->longitude);
@endphp
<div class="mb-3">
    <h2 class="dv-title">{{ $l->driver->name ?? '—' }}</h2>
    <div class="dv-sub">{{ $l->date }}</div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-truck mr-1"></i> {{ __('detail.team') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.driver'), 'value' => $l->driver->name ?? null])
            @include('partials.dv_row', ['label' => __('detail.kelindan'), 'value' => $l->kelindan->name ?? null])
            @include('partials.dv_row', ['label' => __('detail.lorry'), 'value' => $l->lorry->lorryno ?? null])
        </dl>
    </div></div>
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-map-marker mr-1"></i> {{ __('detail.location') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.latitude'), 'value' => $l->latitude])
            @include('partials.dv_row', ['label' => __('detail.longitude'), 'value' => $l->longitude])
            @if($hasPoint)
                <dd><a href="https://www.google.com/maps?q={{ urlencode($l->latitude . ',' . $l->longitude) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa fa-map-o"></i> {{ __('detail.open_map') }}</a></dd>
            @endif
        </dl>
    </div></div>
</div>
@include('partials.detail_scripts')
