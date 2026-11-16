@include('partials.detail_styles')
@php
    $p = $invoicePayment;
    $type = [
        1 => __('invoice_payments.cash'),
        2 => __('invoice_payments.credit'),
        3 => __('invoice_payments.online_bankin'),
        4 => __('invoice_payments.ewallet'),
        5 => __('invoice_payments.cheque') . ($p->chequeno ? ' · ' . $p->chequeno : ''),
    ][$p->type] ?? __('invoice_payments.payment_term_unknown');
    [$statusClass, $statusText] = [
        0 => ['secondary', __('invoice_payments.new')],
        1 => ['success', __('invoice_payments.completed')],
        2 => ['danger', __('invoice_payments.canceled')],
    ][$p->status] ?? ['secondary', '—'];
    $attachment = $p->attachment;
    $isImage = $attachment && preg_match('/\.(jpe?g|png|gif|webp)$/i', $attachment);
@endphp
<div class="mb-3">
    <h2 class="dv-title">
        RM {{ number_format($p->amount, 2) }}
        <span class="badge badge-{{ $statusClass }} align-middle ml-1">{{ $statusText }}</span>
    </h2>
    <div class="dv-sub">{{ implode(' · ', array_filter([$type, $p->customer->company ?? null])) }}</div>
</div>
<div class="row">
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-money mr-1"></i> {{ __('detail.payment') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('invoice_payments.type'), 'value' => $type])
            @include('partials.dv_row', ['label' => __('invoice_payments.amount'), 'value' => 'RM ' . number_format($p->amount, 2)])
            @include('partials.dv_row', ['label' => __('invoice_payments.invoice'), 'value' => $p->invoice->invoiceno ?? null])
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-user-o mr-1"></i> {{ __('detail.customer') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.company'), 'value' => $p->customer->company ?? null])
            @include('partials.dv_row', ['label' => __('detail.code'), 'value' => $p->customer->code ?? null])
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-check-square-o mr-1"></i> {{ __('detail.approval') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('invoice_payments.approve_by'), 'value' => $p->approve_by])
            @include('partials.dv_row', ['label' => __('invoice_payments.approve_at'), 'value' => $p->approve_at])
            @include('partials.dv_row', ['label' => __('invoice_payments.remark'), 'value' => $p->remark])
        </dl>
    </div></div>
</div>
@if($attachment)
<div class="dv-section">
    <div class="dv-section-title"><i class="fa fa-paperclip mr-1"></i> {{ __('invoice_payments.attachment') }}</div>
    <div class="p-3">
        @if($isImage)
            <a href="{{ $attachment }}" target="_blank" rel="noopener"><img src="{{ $attachment }}" alt="" style="max-width:220px;max-height:220px;border:1px solid #e4e7ea;border-radius:4px;"></a>
        @else
            <a href="{{ $attachment }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> {{ basename($attachment) }}</a>
        @endif
    </div>
</div>
@endif
@include('partials.detail_scripts')
