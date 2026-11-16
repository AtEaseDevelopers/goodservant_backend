@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">
        {{ __('detail.buy') }} {{ $foc->quantity }} × {{ $foc->product->name ?? '—' }}
        <i class="fa fa-long-arrow-right text-muted mx-1"></i>
        {{ __('detail.free') }} {{ $foc->free_quantity }} × {{ $foc->freeproduct->name ?? '—' }}
        @include('partials.dv_status', ['active' => $foc->status == 1])
    </h2>
    <div class="dv-sub">{{ $foc->customer->company ?? '—' }}</div>
</div>
<div class="row">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('focs.product') }}</div><div class="dv-fact-value">{{ $foc->product->name ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('focs.free_product') }}</div><div class="dv-fact-value">{{ $foc->freeproduct->name ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('focs.start_date') }}</div><div class="dv-fact-value">{{ $foc->startdate ?: '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('focs.end_date') }}</div><div class="dv-fact-value">{{ $foc->enddate ?: '—' }}</div></div></div>
</div>
@include('partials.detail_scripts')
