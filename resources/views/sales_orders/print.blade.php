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
            background-image: url('{{config('app.url')}}/logo.png');
            width: auto;
            height: 55px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            margin-bottom: 0.5rem;
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
            <td>
                <div class="login-image"></div>
            </td>
        </tr>
        <tr>
            <td>
                <p class="company">{{ env('INVOICE_NAME') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ env('INVOICE_SSM') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ env('INVOICE_ADDRESS1') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ env('INVOICE_ADDRESS2') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ env('INVOICE_ADDRESS3') }}</p>
            </td>
        </tr>
        @if(env('INVOICE_PHONE'))
        <tr>
            <td>
                <p class="address">Tel: {{ env('INVOICE_PHONE') }}</p>
            </td>
        </tr>
        @endif

        <tr>
            <td>
                <br>
                <table id="header">
                    <tr>
                        <td width="35%">
                            <p>Sales Order</p>
                        </td>
                        <td width="65%">
                            <p class="ta-r">{{ $salesOrder['sono'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Sales Order Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($salesOrder['date']),'d-m-Y H:i:s') ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $salesOrder['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $salesOrder['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>

                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $salesOrder['customer']['company'] ?? '-' }}</p>
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
                            <p class="ta-r">Price <br>(RM)</p>
                        </th>
                        <th>
                            <p class="ta-r">Qty</p>
                        </th>
                        <th>
                            <p class="ta-r">Subtotal</p>
                        </th>
                    </tr>
                    @php
                            $totalamount = 0;
                    @endphp
                    @foreach ($salesOrder['salesorderdetail'] as $salesorderdetail)
                        @php
                            $totalamount = ($totalamount ?? 0) + $salesorderdetail['totalprice'];
                        @endphp
                        <tr>
                            <td>
                                <p style="font-size:16px;">{{ $salesorderdetail['product']['name'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($salesorderdetail['price'],2) }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ $salesorderdetail['quantity'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($salesorderdetail['totalprice'],2) }}</p>
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
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Total</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
