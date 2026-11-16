@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">{{ $lorry->lorryno }} @include('partials.dv_status', ['active' => $lorry->status == 1])</h2>
</div>
<div class="dv-section"><div class="dv-section-title">{{ __('detail.remark') }}</div><dl><dd class="mt-1">{{ $lorry->remark ?: '—' }}</dd></dl></div>
@include('partials.detail_scripts')
