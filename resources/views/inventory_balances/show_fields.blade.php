@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">{{ $inventoryBalance->product->name ?? '—' }}</h2>
    <div class="dv-sub">{{ __('detail.lorry') }} {{ $inventoryBalance->lorry->lorryno ?? '—' }}</div>
</div>
<div class="row">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.quantity') }}</div><div class="dv-fact-value">{{ $inventoryBalance->quantity }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.product_code') }}</div><div class="dv-fact-value">{{ $inventoryBalance->product->code ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.lorry') }}</div><div class="dv-fact-value">{{ $inventoryBalance->lorry->lorryno ?? '—' }}</div></div></div>
</div>
@include('partials.detail_scripts')
