<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
            margin-bottom:30px;
            margin-top:30px;
            margin-left:30px;
            margin-right:30px;
        }
        body{
            font-size: 14px;
            margin: 0%;
            font-family: Arial, Helvetica, sans-serif;
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
            height: 55px;
            margin-bottom: 0.5rem;
        }
        .login-image-cell{
            text-align: center;
        }
        .company{
            font-weight: bold;
            text-align: center;
        }
        .address{
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
                <table id="header">
                    <tr>
                        <td width="35%">
                            <p>Delivery Order</p>
                        </td>
                        <td width="65%">
                            <p class="ta-r">{{ $deliveryOrder['dono'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Delivery Order Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($deliveryOrder['date']),'d-m-Y H:i:s') ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Method</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            @if($deliveryOrder['paymentterm']==1)
                                {{ 'Cash' }}
                            @elseif($deliveryOrder['paymentterm']==2)
                                {{ 'Credit'}}
                            @elseif($deliveryOrder['paymentterm']==3)
                                {{ 'Online Banking (QR Code)'}}
                            @elseif($deliveryOrder['paymentterm']==4)
                                {{ 'E-wallet'}}
                            @elseif($deliveryOrder['paymentterm']==5)
                                {{ 'Cheque'}}
                            @endif
                            </p>
                        </td>
                    </tr>

                    @if($deliveryOrder['paymentterm']==5)
                    <tr>
                        <td>
                            <p>Cheque No</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            {{ $deliveryOrder['chequeno'] }}
                            </p>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $deliveryOrder['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $deliveryOrder['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>

                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $deliveryOrder['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="detail">
                    <tr>
                        <th>
                            <p class="ta-l">Product</p>
                        </th>
                        <th>
                            <p class="ta-r">Qty</p>
                        </th>
                    </tr>
                    @foreach ($deliveryOrder['deliveryorderdetail'] as $deliveryorderdetail)
                        <tr>
                            <td>
                                <p style="font-size:16px;">{{ $deliveryorderdetail['product']['name'] ?? '-' }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">X {{ $deliveryorderdetail['quantity'] }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        @if(!empty($deliveryOrder['remark']))
        <tr>
            <td>
                <p style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 8px; font-size: 14px;">Remark : {{ $deliveryOrder['remark'] }}</p>
            </td>
        </tr>
        @endif
    </table>
</body>

</html>
