<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }

    .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #333; padding-bottom: 8px; }
    .header h1 { font-size: 15px; font-weight: bold; }
    .header p  { font-size: 10px; color: #555; }
    .report-title { text-align: center; margin-bottom: 12px; }
    .report-title h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .report-title span { font-size: 10px; color: #666; }

    .section-label { font-size: 11px; font-weight: bold; padding: 4px 0; margin-top: 12px; }

    table.list { width: 100%; border-collapse: collapse; }
    table.list th { background: #e8e8e8; padding: 5px 6px; text-align: left; font-size: 10px; border: 1px solid #ccc; }
    table.list td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    table.list .text-right { text-align: right; }
    table.list tr.subtotal td { background: #f3f3f3; font-weight: bold; }
    table.list tr.grand td { background: #e0e0e0; font-weight: bold; border-top: 2px solid #333; }

    table.summary { width: 50%; border-collapse: collapse; }
    table.summary th { background: #e8e8e8; padding: 5px 6px; text-align: left; font-size: 10px; border: 1px solid #ccc; }
    table.summary td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; }
    table.summary .text-right { text-align: right; }
    table.summary tr.grand td { background: #e0e0e0; font-weight: bold; border-top: 2px solid #333; }

    .footer { margin-top: 16px; border-top: 1px solid #ccc; padding-top: 6px; text-align: center; font-size: 9px; color: #888; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ config('invoice.name') }}</h1>
    <p>{{ config('invoice.ssm') }}</p>
</div>

<div class="report-title">
    <h2>Payment Collection Report</h2>
    <span>
        @if($dateFrom === $dateTo)
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }}
        @else
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}
        @endif
        &nbsp;|&nbsp; Driver: {{ $driverName ?? 'All drivers' }}
        &nbsp;|&nbsp; Generated: {{ now()->format('d-m-Y H:i:s') }}
    </span>
</div>

<div class="section-label">Summary by Payment Method</div>
<table class="summary">
    <thead>
        <tr>
            <th>Payment Method</th>
            <th class="text-right">Payments</th>
            <th class="text-right">Amount (RM)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($methodSummary as $label => $summary)
        <tr>
            <td>{{ $label }}</td>
            <td class="text-right">{{ $summary['count'] }}</td>
            <td class="text-right">{{ number_format($summary['amount'], 2) }}</td>
        </tr>
        @endforeach
        <tr class="grand">
            <td>TOTAL COLLECTED</td>
            <td class="text-right">{{ $rows->count() }}</td>
            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-label">Payments</div>
<table class="list">
    <thead>
        <tr>
            <th style="width:13%">Date / Time</th>
            <th style="width:10%">Payment No</th>
            <th style="width:13%">Invoice No</th>
            <th style="width:26%">Customer</th>
            <th style="width:14%">Collected By</th>
            <th style="width:13%">Method</th>
            <th style="width:11%" class="text-right">Amount (RM)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rowsByCollector as $collector => $collectorRows)
            @foreach($collectorRows as $row)
            <tr>
                <td>{{ $row['time'] }}</td>
                <td>{{ $row['payment_no'] }}</td>
                <td>{{ $row['invoice_no'] }}</td>
                <td>{{ $row['customer'] }}</td>
                <td>{{ $row['collector'] }}</td>
                <td>{{ $row['method'] }}</td>
                <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="6" class="text-right">Total collected by {{ $collector }} ({{ $collectorRows->count() }} payments)</td>
                <td class="text-right">{{ number_format($collectorRows->sum('amount'), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;padding:12px;">No payments collected in this period.</td></tr>
        @endforelse

        @if($rows->isNotEmpty())
        <tr class="grand">
            <td colspan="6" class="text-right">GRAND TOTAL</td>
            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
        </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    Payments recorded in the system, plus invoices paid on the spot by online banking, e-wallet or cheque.
    Credit invoices appear here only when they are paid.
</div>

</body>
</html>
