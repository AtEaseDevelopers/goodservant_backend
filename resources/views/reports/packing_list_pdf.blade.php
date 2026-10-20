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
    table.stops td { padding: 6px 0 5px 0; vertical-align: top; border-bottom: 1px solid #000; }
    table.stops td.name { width: 32%; font-weight: bold; padding-right: 10px; }
    table.stops td.items { width: 68%; }
    table.stops td.empty { text-align: center; font-weight: normal; padding: 10px 0; }

    .totals-title { text-align: center; font-weight: bold; font-size: 13px; margin: 28px 0 16px 0; }

    table.totals { width: 100%; border-collapse: collapse; }
    table.totals td { width: 20%; font-size: 12px; padding: 3px 10px 3px 0; vertical-align: top; white-space: nowrap; }
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
            @php $shown = 0; @endphp
            @foreach($products as $product)
                @if(($row['quantities'][$product->id] ?? 0) > 0)
                    @if($shown > 0), @endif
                    <b>{{ $row['quantities'][$product->id] }}</b> {{ $product->code }}
                    @php $shown++; @endphp
                @endif
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
    $productList = collect($products)->values();
    $columnCount = 5;
    $productCount = $productList->count();
    $perColumn = $productCount > 0 ? (int) ceil($productCount / $columnCount) : 0;
@endphp
<table class="totals">
    @for($r = 0; $r < $perColumn; $r++)
    <tr>
        @for($c = 0; $c < $columnCount; $c++)
            @php $index = ($c * $perColumn) + $r; @endphp
            @if($index < $productCount)
                @php $product = $productList[$index]; @endphp
                <td>{{ $product->code }} <b>{{ $totals[$product->id] ?? 0 }}</b></td>
            @else
                <td></td>
            @endif
        @endfor
    </tr>
    @endfor
</table>

</body>
</html>
