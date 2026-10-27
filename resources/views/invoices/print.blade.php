<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @include('pdf.cjk_font')
        @page {
            margin-bottom:30px;
            margin-top:30px;
            margin-left:30px;
            margin-right:30px;
        }
        body{
            font-size: 14px;
            margin: 0%;
            font-family: notosanssc, sans-serif;
        }
        table{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table th, table td{
            /* border: 1px solid black; */
            font-size: 12px;
        }

        .login-image{
            width: auto;
            height: 100px;
            margin-bottom: 0.5rem;
        }
        .login-image-cell{
            text-align: center;
        }
        .company{
            font-weight: bold;
            font-size: 15px;
            text-align: center;
        }
        .address{
            font-size: 12px;
            text-align: center;
        }
        p{
            margin: 0%;
        }
        .ta-r{
            text-align: right;
        }
        .ta-l{
            text-align: left;
        }
        .paidsummary{
            text-align: center;
            font-weight: bold;
            color: #394068;
        }
        .item-name{
            font-size: 15px;
        }
        .item-calc{
            font-size: 13px;
            padding-bottom: 6px;
        }
        .rule{
            border-top: 1px dashed #000;
        }
    </style>
</head>
<body>
    <table class="invoice">
        <tr>
            <td class="login-image-cell">
                <img class="login-image" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}">
            </td>
        </tr>
        <tr>
            <td>
                <p class="company">{{ config('invoice.name') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ config('invoice.ssm') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ config('invoice.address1') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ config('invoice.address2') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ config('invoice.address3') }}</p>
            </td>
        </tr>
        @if(config('invoice.phone'))
        <tr>
            <td>
                <p class="address">Tel: {{ config('invoice.phone') }}</p>
            </td>
        </tr>
        @endif

        <tr>
            <td>
                <br>
                @php
                    $paymentTypeLabels = [1 => 'Cash', 2 => 'Credit', 3 => 'Online Banking (QR Code)', 4 => 'E-wallet', 5 => 'Cheque'];
                    $docTitle = ((int) $invoice['paymentterm'] === 2) ? 'INVOICE' : 'CASH SALE';
                    $paymentTypeLabel = $paymentTypeLabels[$invoice['paymentterm']] ?? '-';
                @endphp
                <table id="header">
                    <tr>
                        <td width="42%">
                            <p style="font-weight:bold;">{{ $docTitle }}</p>
                        </td>
                        <td width="58%">
                            <p class="ta-r">{{ $invoice['invoiceno'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($invoice['date']),'d-m-Y') ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Type</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $paymentTypeLabel }}</p>
                        </td>
                    </tr>
                    
                    @if($invoice['paymentterm']==5)
                    <tr>
                        <td>
                            <p>Cheque No</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            {{ $invoice['chequeno'] }}
                            </p>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td>
                            <p>Salesperson</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>
                    
                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $invoice['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="detail">
                    @php
                            $totalamount = 0;
                            // The discount is stored as a negative line on a hidden product:
                            // keep it out of the item rows and show it under the subtotal.
                            $discountProductId = \App\Support\InvoiceDiscount::productId();
                            $discountAmount = 0;
                    @endphp
                    @foreach ($invoice['invoicedetail'] as $invoicedetail)
                        @if((int) ($invoicedetail['product_id'] ?? 0) === $discountProductId)
                            @php $discountAmount += abs($invoicedetail['totalprice']); @endphp
                            @continue
                        @endif
                        @php
                            $totalamount = ($totalamount ?? 0) + $invoicedetail['totalprice'];
                            $itemCode = trim($invoicedetail['product']['code'] ?? '');
                            $itemName = trim($invoicedetail['product']['name'] ?? '');
                            $itemLabel = trim($itemCode . ' ' . $itemName);
                        @endphp
                        <tr>
                            <td>
                                <p class="item-name">{{ $itemLabel !== '' ? $itemLabel : '-' }}</p>
                            </td>
                            <td width="32%">
                                <p class="ta-r item-name">RM{{ number_format($invoicedetail['totalprice'],2) }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p class="item-calc">{{ number_format($invoicedetail['quantity'], 3) }} x RM{{ number_format($invoicedetail['price'], 2) }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="total">
                    @if($discountAmount > 0)
                    <tr>
                        <td>
                            <p class="ta-l" style="font-size:16px;">Subtotal</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p class="ta-l" style="font-size:16px;">Discount</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px;">-RM{{ number_format($discountAmount,2) }}</p>
                        </td>
                    </tr>
                    @php $totalamount = $totalamount - $discountAmount; @endphp
                    @endif
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Total</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </th>
                    </tr>
                    @php
                        // Cash sale where the customer handed over more than the
                        // total: print what was received and the change due.
                        $cashReceived = optional($invoice->invoicepayment->where('type', 1)->whereNotNull('cash_received')->first())->cash_received;
                    @endphp
                    <tr>
                        <td>
                            <p class="ta-l" style="font-size:16px;">{{ $paymentTypeLabel }}</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                    @if(!empty($cashReceived) && $cashReceived >= $totalamount)
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Cash Received</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($cashReceived,2) }}</p>
                        </th>
                    </tr>
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Change</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($cashReceived - $totalamount,2) }}</p>
                        </th>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr>
            <td>
                @php
                    $printedAt = $invoice->getRawOriginal('created_at')
                        ? \Carbon\Carbon::parse($invoice->getRawOriginal('created_at'))->format('d/m/Y H:i')
                        : date_format(date_create($invoice['date']), 'd/m/Y');
                @endphp
                <p class="ta-c" style="text-align:center; margin-top:12px;">Receipt In (RM) Malaysia</p>
                <table>
                    <tr>
                        <td><p>{{ $printedAt }}</p></td>
                        <td><p class="ta-r">{{ $invoice['invoiceno'] ?? '-' }}</p></td>
                    </tr>
                </table>
            </td>
        </tr>
        @if(!empty($invoice['remark']))
        <tr>
            <td>
                <p style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 8px; font-size: 14px;">Remark : {{ $invoice['remark'] }}</p>
            </td>
        </tr>
        @endif
    </table>
</body>

</html>
