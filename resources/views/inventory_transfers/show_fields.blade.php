@include('partials.detail_styles')
@php
    $t = $inventoryTransfer;
    [$statusClass, $statusText] = [
        1 => ['warning', __('detail.pending_accept')],
        2 => ['success', __('detail.accepted')],
        3 => ['danger', __('detail.rejected')],
    ][$t->status] ?? ['secondary', '—'];
@endphp
<div class="mb-3">
    <h2 class="dv-title">
        {{ $t->product->name ?? '—' }} × {{ $t->quantity }}
        <span class="badge badge-{{ $statusClass }} align-middle ml-1">{{ $statusText }}</span>
    </h2>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-sign-out mr-1"></i> {{ __('detail.from') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.driver'), 'value' => $t->fromdriver->name ?? null])
            @include('partials.dv_row', ['label' => __('detail.lorry'), 'value' => $t->fromlorry->lorryno ?? null])
        </dl>
    </div></div>
    <div class="col-md-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-sign-in mr-1"></i> {{ __('detail.to') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.driver'), 'value' => $t->todriver->name ?? null])
            @include('partials.dv_row', ['label' => __('detail.lorry'), 'value' => $t->tolorry->lorryno ?? null])
        </dl>
    </div></div>
</div>
<div class="dv-section"><div class="dv-section-title">{{ __('detail.remark') }}</div><dl><dd class="mt-1">{{ $t->remark ?: '—' }}</dd></dl></div>
@include('partials.detail_scripts')
