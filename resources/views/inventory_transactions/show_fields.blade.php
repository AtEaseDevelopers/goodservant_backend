@include('partials.detail_styles')
@php
    $t = $inventoryTransaction;
    [$typeClass, $typeText] = [
        1 => ['success', __('detail.stock_in')],
        2 => ['warning', __('detail.stock_out')],
        3 => ['info', __('detail.invoice')],
        4 => ['primary', __('detail.transfer')],
        5 => ['danger', __('detail.wastage')],
    ][$t->type] ?? ['secondary', '—'];
@endphp
<div class="mb-3">
    <h2 class="dv-title">
        {{ $t->product->name ?? '—' }}
        <span class="badge badge-{{ $typeClass }} align-middle ml-1">{{ $typeText }}</span>
    </h2>
    <div class="dv-sub">{{ __('detail.lorry') }} {{ $t->lorry->lorryno ?? '—' }}</div>
</div>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.quantity') }}</div><div class="dv-fact-value">{{ $t->quantity }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.type') }}</div><div class="dv-fact-value">{{ $typeText }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.user') }}</div><div class="dv-fact-value">{{ $t->user ?: '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.date') }}</div><div class="dv-fact-value">{{ $t->created_at ?: '—' }}</div></div></div>
</div>
<div class="dv-section"><div class="dv-section-title">{{ __('detail.remark') }}</div><dl><dd class="mt-1">{{ $t->remark ?: '—' }}</dd></dl></div>
@include('partials.detail_scripts')
