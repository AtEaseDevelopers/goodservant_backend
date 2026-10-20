<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 14mm 16mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }

    table.header { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
    table.header td { font-weight: bold; font-size: 14px; vertical-align: bottom; }
    table.header td.driver { width: 34%; text-align: left; }
    table.header td.title { width: 32%; text-align: center; }
    table.header td.spacer { width: 34%; }

    table.stops { width: 100%; border-collapse: collapse; }
    table.stops td { padding: 8px 0 7px 0; vertical-align: top; border-bottom: 1px solid #000; }
    table.stops td.name { width: 32%; font-weight: bold; font-size: 16px; padding-right: 10px; }
    table.stops td.items { width: 68%; font-size: 15px; }
    .item-line { margin: 0 0 3px 0; }
    table.stops td.empty { text-align: center; font-weight: normal; padding: 10px 0; }

    .totals-title { text-align: center; font-weight: bold; font-size: 11px; margin: 22px 0 8px 0; }

    table.totals { width: 100%; border-collapse: collapse; }
    table.totals td { width: 33%; font-size: 9px; padding: 2px 8px 2px 0; vertical-align: top; }
    table.totals td b { padding-left: 8px; }
</style>
</head>
<body>

<table class="header">
    <tr>
        <td class="driver">DRIVER: {{ strtoupper($driver->name) }}</td>
        <td class="title">Packing List {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</td>
        <td class="spacer"></td>
    </tr>
</table>

@php
    $packedRows = collect($rows)->filter(function ($row) {
        foreach ($row['quantities'] as $qty) {
            if ($qty > 0) {
                return true;
            }
        }
        return false;
    })->values();
@endphp

<table class="stops">
    @forelse($packedRows as $row)
    <tr>
        <td class="name">{{ $row['customer_name'] }}</td>
        <td class="items">
            @php
                $merged = [];
                foreach ($products as $product) {
                    $qty = $row['quantities'][$product->id] ?? 0;
                    if ($qty <= 0) {
                        continue;
                    }
                    $merged[$product->name] = ($merged[$product->name] ?? 0) + $qty;
                }
            @endphp
            @foreach($merged as $name => $qty)
                <div class="item-line"><b>{{ $qty }}</b> {{ $name }}</div>
            @endforeach
        </td>
    </tr>
    @empty
    <tr>
        <td class="empty" colspan="2">No tasks found for this driver on this date.</td>
    </tr>
    @endforelse
</table>

<div class="totals-title">TOTAL PACKING LIST QUANTITY</div>

@php
    $mergedTotals = [];
    foreach ($products as $product) {
        $qty = $totals[$product->id] ?? 0;
        if ($qty <= 0) {
            continue;
        }
        $mergedTotals[$product->name] = ($mergedTotals[$product->name] ?? 0) + $qty;
    }
    $totalList = collect($mergedTotals);
    $columnCount = 3;
    $productCount = $totalList->count();
    $perColumn = $productCount > 0 ? (int) ceil($productCount / $columnCount) : 0;
    $totalNames = $totalList->keys()->values();
@endphp
<table class="totals">
    @for($r = 0; $r < $perColumn; $r++)
    <tr>
        @for($c = 0; $c < $columnCount; $c++)
            @php $index = ($c * $perColumn) + $r; @endphp
            @if($index < $productCount)
                @php $name = $totalNames[$index]; @endphp
                <td>{{ $name }} <b>{{ $mergedTotals[$name] }}</b></td>
            @else
                <td></td>
            @endif
        @endfor
    </tr>
    @endfor
</table>

</body>
</html>
