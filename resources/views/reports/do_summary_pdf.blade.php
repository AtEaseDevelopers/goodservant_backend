<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 4px 6px; }
        th { text-align: left; background: #f2f2f2; }
        .ta-r { text-align: right; }
        .do-title { font-size: 13px; font-weight: bold; margin: 12px 0 4px; }
    </style>
</head>
<body>
    <h1>DO Summary</h1>
    <p>{{ $customer->company ?? '-' }}</p>
    <p>{{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</p>

    @foreach($orders as $order)
        <p class="do-title">{{ $order->dono ?? ('DO #'.$order->id) }} — {{ $order->date }}</p>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="ta-r">Qty</th>
                    <th class="ta-r">Price (RM)</th>
                    <th class="ta-r">Total (RM)</th>
                </tr>
            </thead>
            <tbody>
                @php $orderTotal = 0; @endphp
                @foreach($order->deliveryorderdetail as $line)
                    @if(\App\Support\InvoiceDiscount::isDiscountProduct($line->product_id))
                        @continue
                    @endif
                    @php $orderTotal += $line->totalprice; @endphp
                    <tr>
                        <td>{{ trim(($line->product->code ?? '') . ' ' . ($line->product->name ?? '')) }}</td>
                        <td class="ta-r">{{ $line->quantity }}</td>
                        <td class="ta-r">{{ number_format($line->price, 2) }}</td>
                        <td class="ta-r">{{ number_format($line->totalprice, 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="3" class="ta-r">DO total</th>
                    <th class="ta-r">{{ number_format($orderTotal, 2) }}</th>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
