<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
            margin-top: 240px;
            margin-bottom: 60px;
            margin-left: 30px;
            margin-right: 30px;
        }
        body {
            font-size: 11px;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }
        p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .ta-r { text-align: right; }
        .ta-c { text-align: center; }
        .header {
            position: fixed;
            top: -240px;
            left: 0;
            right: 0;
        }
        .letterhead p { text-align: center; font-size: 14px; }
        .letterhead .company-name { font-size: 22px; font-weight: bold; }
        .letterhead .company-name .reg-no { font-size: 14px; font-weight: normal; }
        .doc-title-wrap { position: relative; padding-top: 4px; }
        .doc-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
        }
        .doc-title-no {
            position: absolute;
            top: 4px;
            right: 0;
        }
        .info-boxes td { border: 1px solid #000; vertical-align: top; padding: 4px 6px; }
        .customer-box p { margin-bottom: 2px; }
        .customer-box .name { font-weight: bold; }
        .meta-box table td { border: none; padding: 1px 0; font-size: 10.5px; }
        .meta-box .label { width: 90px; }
        .meta-box .colon { width: 10px; }
        .item-table thead th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
            font-size: 10.5px;
            text-align: left;
        }
        .item-table tbody td {
            padding: 2px 4px;
            font-size: 10.5px;
            vertical-align: top;
        }
        .item-table .col-item { width: 5%; }
        .item-table .col-desc { width: 38%; }
        .item-table .col-do { width: 14%; }
        .item-table .col-qty { width: 8%; text-align: right; }
        .item-table .col-uom { width: 10%; }
        .item-table .col-disc { width: 7%; text-align: right; }
        .item-table .col-total { width: 18%; text-align: right; }
        .footer-block { margin-top: 10px; }
        .amount-words-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px; vertical-align: top; }
        .total-box { border: 1px solid #000; padding: 4px 8px; font-weight: bold; }
        .notes { font-size: 10px; margin-top: 8px; }
        .signature-line { margin-top: 60px; border-top: 1px solid #000; width: 220px; font-size: 10px; font-weight: bold; text-align: center; padding-top: 2px; }
        .page-current:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <table class="letterhead">
            <tr><td><p class="company-name">{{ $invoice['customer']['groupcompany']->name ?? config('invoice.name') }}</p></td></tr>
            <tr><td><p>{{ $invoice['customer']['groupcompany']->ssm ?? config('invoice.ssm') }}</p></td></tr>
            <tr><td><p>{{ $invoice['customer']['groupcompany']->address1 ?? config('invoice.address1') }}</p></td></tr>
            <tr><td><p>{{ $invoice['customer']['groupcompany']->address2 ?? config('invoice.address2') }}</p></td></tr>
            <tr><td><p>{{ $invoice['customer']['groupcompany']->address3 ?? config('invoice.address3') }}</p></td></tr>
            @if(config('invoice.phone'))
            <tr><td><p>TEL: {{ config('invoice.phone') }}</p></td></tr>
            @endif
        </table>

        <div class="doc-title-wrap">
            <p class="doc-title">INVOICE</p>
            <p class="doc-title-no">No. : {{ $invoice['invoiceno'] ?? '-' }}</p>
        </div>

        <table class="info-boxes">
            <tr>
                <td width="55%" class="customer-box">
                    <p class="name">{{ $invoice['customer']['company'] ?? '-' }}</p>
                    @if(!empty($invoice['customer']['address']))<p>{{ $invoice['customer']['address'] }}</p>@endif
                    @if(!empty($invoice['customer']['phone']))<p>TEL: {{ $invoice['customer']['phone'] }}</p>@endif
                </td>
                <td width="45%" class="meta-box">
                    @php
                        $paymentTermLabels = [1 => 'Cash', 2 => 'Credit', 3 => 'Online BankIn', 4 => 'E-wallet', 5 => 'Cheque'];
                    @endphp
                    <table>
                        <tr><td class="label">Our D/O No.</td><td class="colon">:</td><td>{{ $sourceDoNo ?? '-' }}</td></tr>
                        <tr><td class="label">Terms</td><td class="colon">:</td><td>{{ $paymentTermLabels[$invoice['paymentterm']] ?? '-' }}</td></tr>
                        <tr><td class="label">Date</td><td class="colon">:</td><td>{{ $invoice['date'] ?? '-' }}</td></tr>
                        <tr><td class="label">Page</td><td class="colon">:</td><td><span class="page-current"></span> of {{ $totalPages }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="item-table">
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th class="col-desc">Description</th>
                <th class="col-do">D/O No.</th>
                <th class="col-qty">Qty</th>
                <th class="col-uom">UOM</th>
                <th class="col-disc">Disc.</th>
                <th class="col-total">Total<br>RM</th>
            </tr>
        </thead>
        <tbody>
            @php $totalamount = 0; @endphp
            @foreach ($invoice['invoicedetail'] as $i => $invoicedetail)
                @php
                    $totalamount = ($totalamount ?? 0) + $invoicedetail['totalprice'];
                    $itemLabel = $invoicedetail['product']['name'] ?? '';
                    $doNo = $invoicedetail['deliveryorder']['dono'] ?? '';
                @endphp
                <tr>
                    <td class="col-item">{{ $i + 1 }}.</td>
                    <td class="col-desc">{{ $itemLabel }}</td>
                    <td class="col-do">{{ $doNo }}</td>
                    <td class="col-qty">{{ $invoicedetail['quantity'] }}</td>
                    <td class="col-uom">UNIT</td>
                    <td class="col-disc"></td>
                    <td class="col-total">{{ number_format($invoicedetail['totalprice'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-block">
        <table>
            <tr class="amount-words-row">
                <td width="70%">{{ \App\Support\NumberToWords::ringgit($totalamount) }}</td>
                <td width="30%" class="total-box ta-r">Total &nbsp; {{ number_format($totalamount, 2) }}</td>
            </tr>
        </table>

        <div class="notes">
            <p>Notes :</p>
            <p>1. All cheques should be crossed and made payable to</p>
            <p>&nbsp;&nbsp;&nbsp;{{ $invoice['customer']['groupcompany']->name ?? config('invoice.name') }}</p>
            <p>2. Goods sold are neither returnable nor refundable. Otherwise a cancellation fee of 20% on purchase price will be imposed.</p>
        </div>

        <div class="signature-line">Authorised Signature</div>
    </div>
</body>
</html>
