@include('partials.detail_styles')
@php $s = $servicedetails; @endphp
<div class="mb-3">
    <h2 class="dv-title">{{ __("lorry_service.type_" . str_replace(" ", "_", strtolower($s->type))) }}</h2>
    <div class="dv-sub">{{ __('detail.lorry') }} {{ $s->lorry->lorryno ?? '—' }}</div>
</div>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('lorry_service.date') }}</div><div class="dv-fact-value">{{ $s->date ?: '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('lorry_service.next_date') }}</div><div class="dv-fact-value">{{ $s->nextdate ?: '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('lorry_service.amount') }}</div><div class="dv-fact-value">RM {{ number_format($s->amount, 2) }}</div></div></div>
</div>
<div class="dv-section"><div class="dv-section-title">{{ __('detail.remark') }}</div><dl><dd class="mt-1">{{ $s->remark ?: '—' }}</dd></dl></div>
@include('partials.detail_scripts')
