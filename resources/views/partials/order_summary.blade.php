{{--
    Summary block for invoices, sales orders and delivery orders.
    Params: $order (model), $number (document no.), $total (float|null), $source (html badge|null)
--}}
@include('partials.detail_styles')
@php
    $term = [
        1 => __('detail.term_cash'),
        2 => __('detail.term_credit'),
        3 => __('detail.term_online'),
        4 => __('detail.term_ewallet'),
        5 => __('detail.term_cheque'),
    ][$order->paymentterm] ?? __('detail.unknown');
    [$statusClass, $statusText] = [
        0 => ['secondary', __('detail.status_new')],
        1 => ['success', __('detail.status_completed')],
        2 => ['danger', __('detail.status_cancelled')],
    ][$order->status] ?? ['secondary', __('detail.unknown')];
    // Invoices deleted by the driver in the app (kept as status 2).
    $deletedAt = $order->driver_deleted_at ?? null;
    if ($deletedAt) {
        [$statusClass, $statusText] = ['dark', __('detail.status_deleted')];
    }
    $customer = $order->customer;
@endphp

<div class="mb-3">
    <h2 class="dv-title">
        {{ $number }}
        <span class="badge badge-{{ $statusClass }} align-middle ml-1">{{ $statusText }}</span>
        @isset($source){!! $source !!}@endisset
    </h2>
    <div class="dv-sub">{{ implode(' · ', array_filter([$order->date, $customer->company ?? null])) }}</div>
    @if($deletedAt)
        <div class="alert alert-dark py-2 mt-2 mb-0">
            <i class="fa fa-trash mr-1"></i>
            {{ __('detail.deleted_by_driver', ['driver' => optional($order->deletedByDriver)->name ?? '-', 'date' => date('d-m-Y H:i', strtotime($deletedAt))]) }}
        </div>
    @endif
</div>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.date') }}</div>
        <div class="dv-fact-value">{{ $order->date ?: '—' }}</div>
    </div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.payment_term') }}</div>
        <div class="dv-fact-value">{{ $term }}</div>
    </div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.driver') }}</div>
        <div class="dv-fact-value">{{ $order->driver->name ?? '—' }}</div>
    </div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.total') }}</div>
        <div class="dv-fact-value">{{ isset($total) ? 'RM ' . number_format($total, 2) : '—' }}</div>
    </div></div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-user-o mr-1"></i> {{ __('detail.customer') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.company'), 'value' => $customer->company ?? null])
            @include('partials.dv_row', ['label' => __('detail.second_description'), 'value' => $customer->second_description ?? null])
            @include('partials.dv_row', ['label' => __('detail.code'), 'value' => $customer->code ?? null])
            @include('partials.dv_row', ['label' => __('detail.phone'), 'value' => $customer->phone ?? null])
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-truck mr-1"></i> {{ __('detail.team') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.driver'), 'value' => $order->driver->name ?? null])
            @include('partials.dv_row', ['label' => __('detail.kelindan'), 'value' => $order->kelindan->name ?? null])
            @if($order->agent->name ?? null)
                @include('partials.dv_row', ['label' => __('detail.agent'), 'value' => $order->agent->name])
            @endif
            @if($order->supervisor->name ?? null)
                @include('partials.dv_row', ['label' => __('detail.supervisor'), 'value' => $order->supervisor->name])
            @endif
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-sticky-note-o mr-1"></i> {{ __('detail.remark') }}</div>
        <dl>
            <dd class="mt-1">{{ $order->remark ?: '—' }}</dd>
            @if($order->paymentAttachments->isNotEmpty())
                <dt class="mt-2">{{ __('detail.payment_attachments') }}</dt>
                <dd>
                    @foreach($order->paymentAttachments as $attachment)
                        <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="d-inline-block mr-2 mb-2">
                            <img src="{{ $attachment->url }}" alt="" style="width:72px;height:72px;object-fit:cover;border:1px solid #e4e7ea;border-radius:4px;">
                        </a>
                    @endforeach
                </dd>
            @endif
        </dl>
    </div></div>
</div>
