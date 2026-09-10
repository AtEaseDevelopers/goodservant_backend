<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }

    .title-bar { display: table; width: 100%; margin-bottom: 6px; }
    .title-bar .driver { display: table-cell; text-align: left; font-weight: bold; font-size: 12px; }
    .title-bar .title { display: table-cell; text-align: center; font-weight: bold; font-size: 12px; }

    table.packing-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.packing-table th, table.packing-table td {
        border: 1px solid #333; padding: 2px 4px; font-size: 9px; text-align: center;
    }
    table.packing-table th { background: #f6d87a; font-weight: bold; }
    table.packing-table td.col-no { width: 3%; }
    table.packing-table td.col-customer, table.packing-table th.col-customer { text-align: left; width: 14%; }
    table.packing-table tr.total-row td, table.packing-table tr.total-row th { background: #f6d87a; font-weight: bold; }

    .legend { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .legend td { font-size: 8px; padding: 1px 6px; vertical-align: top; }

    .footer { margin-top: 16px; border-top: 1px solid #ccc; padding-top: 6px; text-align: center; font-size: 8px; color: #888; }
</style>
</head>
<body>

<div class="title-bar">
    <div class="driver">DRIVER: {{ strtoupper($driver->name) }}</div>
    <div class="title">Packing List {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</div>
</div>

<table class="packing-table">
    <thead>
        <tr>
            <th class="col-no">No.</th>
            <th class="col-customer">Customer/Product</th>
            @foreach($products as $product)
                <th>{{ $product->code }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td class="col-no">{{ $row['customer_id'] }}</td>
            <td class="col-customer">{{ $row['customer_name'] }}</td>
            @foreach($products as $product)
                <td>{{ $row['quantities'][$product->id] > 0 ? $row['quantities'][$product->id] : '' }}</td>
            @endforeach
        </tr>
        @empty
        <tr><td colspan="{{ $products->count() + 2 }}" style="text-align:center;padding:10px;">No tasks found for this driver on this date.</td></tr>
        @endforelse

        @if($rows->isNotEmpty())
        <tr class="total-row">
            <th class="col-no"></th>
            <th class="col-customer">Total</th>
            @foreach($products as $product)
                <th>{{ $totals[$product->id] }}</th>
            @endforeach
        </tr>
        @endif
    </tbody>
</table>

<table class="legend">
    <tr>
        @foreach($products->chunk(ceil(max($products->count(), 1) / 4)) as $chunk)
        <td>
            @foreach($chunk as $product)
                {{ $product->code }} - {{ $product->name }}<br>
            @endforeach
        </td>
        @endforeach
    </tr>
</table>

<div class="footer">
    This report was generated automatically &mdash; {{ now()->format('d-m-Y H:i:s') }}
</div>

</body>
</html>
