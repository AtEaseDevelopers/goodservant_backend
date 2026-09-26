<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }

    .title-bar { display: table; width: 100%; margin-bottom: 10px; }
    .title-bar .driver { display: table-cell; text-align: left; font-weight: bold; font-size: 13px; }
    .title-bar .title { display: table-cell; text-align: right; font-weight: bold; font-size: 13px; }

    .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .meta td { padding: 2px 4px; font-size: 10px; }
    .meta td.label { font-weight: bold; width: 120px; }

    table.count-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.count-table th, table.count-table td {
        border: 1px solid #333; padding: 4px 6px; font-size: 10px;
    }
    table.count-table th { background: #f6d87a; font-weight: bold; text-align: center; }
    table.count-table td.num { text-align: center; }
    table.count-table td.variance-pos { text-align: center; color: #28a745; }
    table.count-table td.variance-neg { text-align: center; color: #dc3545; }

    .footer { margin-top: 16px; border-top: 1px solid #ccc; padding-top: 6px; text-align: center; font-size: 8px; color: #888; }
</style>
</head>
<body>

<div class="title-bar">
    <div class="driver">DRIVER: {{ strtoupper($driver->name) }}</div>
    <div class="title">Stock Count Report</div>
</div>

<table class="meta">
    <tr>
        <td class="label">Submitted At</td>
        <td>{{ optional($count->created_at)->format('d-m-Y H:i:s') }}</td>
        <td class="label">Status</td>
        <td>{{ ucfirst($count->status) }}</td>
    </tr>
    @if($count->status === \App\Models\InventoryCount::STATUS_APPROVED)
    <tr>
        <td class="label">Approved At</td>
        <td>{{ optional($count->approved_at)->format('d-m-Y H:i:s') }}</td>
        <td class="label">Approved By</td>
        <td>{{ optional(\App\Models\User::find($count->approved_by))->name ?? '-' }}</td>
    </tr>
    @endif
    @if($count->status === \App\Models\InventoryCount::STATUS_REJECTED)
    <tr>
        <td class="label">Rejected At</td>
        <td>{{ optional($count->rejected_at)->format('d-m-Y H:i:s') }}</td>
        <td class="label">Rejected By</td>
        <td>{{ optional(\App\Models\User::find($count->rejected_by))->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Rejection Reason</td>
        <td colspan="3">{{ $count->rejection_reason ?? '-' }}</td>
    </tr>
    @endif
    @if($count->remarks)
    <tr>
        <td class="label">Remarks</td>
        <td colspan="3">{{ $count->remarks }}</td>
    </tr>
    @endif
</table>

<table class="count-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Product</th>
            <th>System Qty</th>
            <th>Counted Qty</th>
            <th>Variance</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        @php
            // counted_quantity may be an empty string when a product hasn't
            // been counted yet - is_numeric() guards the subtraction below.
            $countedRaw = $item['counted_quantity'] ?? 0;
            $counted = is_numeric($countedRaw) ? $countedRaw + 0 : 0;
            $currentRaw = $item['current_quantity'] ?? 0;
            $current = is_numeric($currentRaw) ? $currentRaw + 0 : 0;
            $variance = $counted - $current;
        @endphp
        <tr>
            <td>{{ $item['product_code'] }}</td>
            <td>{{ $item['product_name'] }}</td>
            <td class="num">{{ $item['current_quantity'] }}</td>
            <td class="num">{{ $item['counted_quantity'] }}</td>
            <td class="{{ $variance < 0 ? 'variance-neg' : ($variance > 0 ? 'variance-pos' : 'num') }}">{{ $variance > 0 ? '+' . $variance : $variance }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:10px;">No items in this stock count.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    This report was generated automatically &mdash; {{ now()->format('d-m-Y H:i:s') }}
</div>

</body>
</html>
