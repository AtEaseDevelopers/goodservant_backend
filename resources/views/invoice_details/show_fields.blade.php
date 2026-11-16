@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">{{ $invoiceDetail->product->name ?? '—' }}</h2>
    <div class="dv-sub">{{ __('detail.invoice') }} {{ $invoiceDetail->invoice->invoiceno ?? '—' }}</div>
</div>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.quantity') }}</div><div class="dv-fact-value">{{ $invoiceDetail->quantity }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.price') }}</div><div class="dv-fact-value">RM {{ number_format($invoiceDetail->price, 2) }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('detail.line_total') }}</div><div class="dv-fact-value">RM {{ number_format($invoiceDetail->totalprice, 2) }}</div></div></div>
</div>
<div class="dv-section"><div class="dv-section-title">{{ __('detail.remark') }}</div><dl><dd class="mt-1">{{ $invoiceDetail->remark ?: '—' }}</dd></dl></div>
@include('partials.detail_scripts')
