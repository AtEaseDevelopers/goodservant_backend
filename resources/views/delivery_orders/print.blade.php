<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
    <style>
        @include('pdf.cjk_font') @page {
            margin-bottom: 8px;
            margin-top: 6px;
            margin-left: 12px;
            margin-right: 12px;
        }

        body {
            font-size: 14px;
            margin: 0%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table th,
        table td {
            font-size: 12px;
        }

        .login-image {
            width: 168px;
            height: 106px;
            margin-bottom: 2px;
        }

        .login-image-cell {
            text-align: center;
        }

        .company {
            font-weight: bold;
            font-size: 15px;
            text-align: center;
        }

        .address {
            font-size: 12px;
            text-align: center;
        }

        p {
            margin: 0%;
        }

        .ta-r {
            text-align: right;
        }

        .ta-l {
            text-align: left;
        }

        .item-name {
            font-size: 20px;
            padding-top: 8px;
            padding-bottom: 8px;
            font-weight: normal;
            font-family: notosanssc, sans-serif;
        }

        .rule {
            margin: 6px 0 4px;
            font-size: 8px;
            line-height: 8px;
            letter-spacing: 2px;
            overflow: hidden;
            white-space: nowrap;
            color: #000;
        }
    </style>
</head>

<body>
    <table class="invoice">
        <tr>
            <td class="login-image-cell">
                <img class="login-image"
                    src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}">
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
        @if (config('invoice.phone'))
            <tr>
                <td>
                    <p class="address">Tel: {{ config('invoice.phone') }}</p>
                </td>
            </tr>
        @endif

        <tr>
            <td>
                @php
                    $paymentTypeLabels = [
                        1 => 'Cash',
                        2 => 'Credit',
                        3 => 'Online Banking (QR Code)',
                        4 => 'E-wallet',
                        5 => 'Cheque',
                    ];
                    $paymentTypeLabel = $paymentTypeLabels[$deliveryOrder['paymentterm']] ?? '-';
                @endphp
                <table id="header">
                    <tr>
                        <td width="42%">
                            <p style="font-weight:bold;">DELIVERY ORDER</p>
                        </td>
                        <td width="58%">
                            <p class="ta-r">{{ $deliveryOrder['dono'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($deliveryOrder['date']), 'd-m-Y') ?? '-' }}</p>
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

                    @if ($deliveryOrder['paymentterm'] == 5)
                        <tr>
                            <td>
                                <p>Cheque No</p>
                            </td>
                            <td>
                                <p class="ta-r">{{ $deliveryOrder['chequeno'] }}</p>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-weight:normal; font-family: notosanssc, sans-serif;">{{ $deliveryOrder['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r cjk">{{ $deliveryOrder['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td height="15">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            {{-- Normal weight: bold makes Dompdf drop Chinese glyphs (shown as ???). --}}
                            <p class="ta-r" style="font-size:16px; font-weight:normal; font-family: notosanssc, sans-serif;">
                                {{ $deliveryOrder['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <p class="rule">
                                ................................................................................................
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table id="detail">
                    @foreach ($deliveryOrder['deliveryorderdetail'] as $deliveryorderdetail)
                        @php
                            $product = $deliveryorderdetail['product'] ?? [];
                            $itemName = trim($product['name'] ?? '');
                            $itemLabel = $itemName;
                            $qtyLabel = rtrim(rtrim(number_format((float) $deliveryorderdetail['quantity'], 3, '.', ''), '0'), '.');
                        @endphp
                        <tr>
                            <td>
                                <p class="item-name">{{ $itemLabel !== '' ? $itemLabel : '-' }}</p>
                            </td>
                            <td width="28%">
                                <p class="ta-r item-name">{{ $qtyLabel !== '' ? $qtyLabel : '-' }}</p>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2">
                            <p class="rule">
                                ................................................................................................
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <p style="margin-top:28px; font-size:16px;">Received by:</p>
                <p style="margin-top:70px;">________________________________________</p>
                <p style="margin-top:36px;">&nbsp;</p>
            </td>
        </tr>
        @if (!empty($deliveryOrder['remark']))
            <tr>
                <td>
                    <p class="cjk" style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 8px; font-size: 14px;">Remark
                        : {{ $deliveryOrder['remark'] }}</p>
                </td>
            </tr>
        @endif
    </table>
</body>

</html>
