@include('partials.detail_styles')
@php $t = $taskTransfer; @endphp
<div class="mb-3">
    <h2 class="dv-title">{{ $t->fromdriver->name ?? '—' }} <i class="fa fa-long-arrow-right text-muted mx-1"></i> {{ $t->todriver->name ?? '—' }}</h2>
    <div class="dv-sub">{{ $t->created_at }}</div>
</div>
<div class="dv-section">
    <div class="dv-section-title"><i class="fa fa-tasks mr-1"></i> {{ __('detail.task') }}</div>
    <dl>
        @include('partials.dv_row', ['label' => __('detail.customer'), 'value' => $t->task->customer->company ?? null])
        @include('partials.dv_row', ['label' => __('detail.date'), 'value' => $t->task->date ?? null])
        @include('partials.dv_row', ['label' => __('detail.invoice'), 'value' => $t->task->invoice->invoiceno ?? null])
    </dl>
</div>
@include('partials.detail_scripts')
