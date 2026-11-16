@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">{{ $assign->customer->company ?? '—' }}</h2>
    <div class="dv-sub">{{ __('assign.sequence') }} {{ $assign->sequence }}</div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-truck mr-1"></i> {{ __('detail.driver') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('assign.driver_name'), 'value' => $assign->driver->name ?? null])
            @include('partials.dv_row', ['label' => __('assign.driver_code'), 'value' => $assign->driver->employeeid ?? null])
        </dl>
    </div></div>
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-user-o mr-1"></i> {{ __('detail.customer') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('assign.customer_company'), 'value' => $assign->customer->company ?? null])
            @include('partials.dv_row', ['label' => __('assign.customer_code'), 'value' => $assign->customer->code ?? null])
        </dl>
    </div></div>
</div>
@include('partials.detail_scripts')
