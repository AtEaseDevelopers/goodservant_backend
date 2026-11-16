@include('partials.detail_styles')
@php
    [$statusClass, $statusText] = [
        0 => ['secondary', __('tasks.new')],
        1 => ['warning', __('tasks.in_progress')],
        8 => ['success', __('tasks.completed')],
        9 => ['danger', __('tasks.cancelled')],
    ][$task->status] ?? ['secondary', '—'];
@endphp
<div class="mb-3">
    <h2 class="dv-title">{{ $task->customer->company ?? '—' }} <span class="badge badge-{{ $statusClass }} align-middle ml-1">{{ $statusText }}</span></h2>
    <div class="dv-sub">{{ $task->date }}</div>
</div>
<div class="row">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('tasks.driver') }}</div><div class="dv-fact-value">{{ $task->driver->name ?? '—' }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('tasks.sequence') }}</div><div class="dv-fact-value">{{ $task->sequence }}</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact"><div class="dv-fact-label">{{ __('tasks.invoice') }}</div><div class="dv-fact-value">{{ $task->invoice->invoiceno ?? '—' }}</div></div></div>
</div>
@include('partials.detail_scripts')
