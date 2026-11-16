@include('partials.detail_styles')
@php
    $money = fn($v) => is_numeric($v) ? 'RM ' . number_format($v, 2) : ($v ?: '—');
    $facts = [
        [__('trips.sales'), $trip->sales, true],
        [__('trips.cash'), $trip->cash, false],
        [__('trips.cash_left'), $trip->cash_left, false],
        [__('trips.credit'), $trip->credit, false],
        [__('trips.online_bank'), $trip->onlinebank, false],
        [__('trips.tng'), $trip->tng, false],
        [__('trips.cheque'), $trip->cheque, false],
    ];
@endphp
<div class="mb-3">
    <h2 class="dv-title">{{ $trip->driver->name ?? __('trips.end_trip_summary') }}</h2>
    <div class="dv-sub">{{ implode(' · ', array_filter([$trip->date ?? null, $trip->lorry->lorryno ?? null, $trip->kelindan->name ?? null])) }}</div>
</div>
<div class="row">
    @foreach($facts as [$label, $value, $main])
        <div class="col-6 col-md-3 mb-2"><div class="dv-fact" @if($main) style="background:#e8f4fb" @endif>
            <div class="dv-fact-label">{{ $label }}</div>
            <div class="dv-fact-value">{{ $money($value) }}</div>
        </div></div>
    @endforeach
</div>
