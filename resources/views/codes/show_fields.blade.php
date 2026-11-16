@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">
        {{ $code->description }}
        <span class="badge badge-light border align-middle ml-1">{{ $code->code }}</span>
    </h2>
</div>
<div class="row">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('codes.value') }}</div><div class="dv-fact-value">{{ $code->value ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('codes.sequence') }}</div><div class="dv-fact-value">{{ $code->sequence ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('codes.created_at') }}</div><div class="dv-fact-value">{{ $code->created_at ?: '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('codes.updated_at') }}</div><div class="dv-fact-value">{{ $code->updated_at ?: '—' }}</div></div></div>
</div>
@include('partials.detail_scripts')
