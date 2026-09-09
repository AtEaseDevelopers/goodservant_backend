@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('trips.index') }}">{{ __('trips.trips') }}</a>
        </li>
        <li class="breadcrumb-item active">Daily Sales Report</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Daily Sales Report - Trip #{{ $endTrip->id }}</strong>
                            <a href="{{ route('trips.report.pdf', request()->route('id')) }}" target="_blank" class="btn btn-sm btn-primary float-right ml-2">
                                <i class="fa fa-file-pdf-o"></i> Download PDF
                            </a>
                            <a href="{{ route('trips.index') }}" class="btn btn-light float-right">Back</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width:20%">Driver</th>
                                    <td style="width:30%">{{ $startTrip?->driver?->name ?? $endTrip->driver?->name ?? '-' }}</td>
                                    <th style="width:20%">Lorry</th>
                                    <td style="width:30%">{{ $startTrip?->lorry?->lorryno ?? $endTrip->lorry?->lorryno ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Kelindan</th>
                                    <td>{{ $startTrip?->kelindan?->name ?? $endTrip->kelindan?->name ?? '-' }}</td>
                                    <th>Closing Cash</th>
                                    <td>RM {{ number_format($endTrip->cash ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Start Time</th>
                                    <td>{{ $startTime ? $startTime->format('d-m-Y H:i:s') : '-' }}</td>
                                    <th>End Time</th>
                                    <td>{{ $endTime->format('d-m-Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td colspan="3">
                                        @if($duration)
                                            {{ $duration->h }} hour(s) {{ $duration->i }} minute(s)
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Payment Summary</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th class="text-right">Amount (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentLabels as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-right">{{ number_format($breakdown[$key] ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        <td>TOTAL</td>
                                        <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Stock Movement</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Product</th>
                                            <th>Opening Stock</th>
                                            <th>Admin In</th>
                                            <th>Admin Out</th>
                                            <th>Invoice</th>
                                            <th>DO</th>
                                            <th>Wastage</th>
                                            <th>Closing Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($stockMovements as $row)
                                        <tr>
                                            <td class="text-left">{{ $row['product_name'] }}</td>
                                            <td>{{ $row['opening_stock'] }}</td>
                                            <td class="{{ $row['admin_in'] > 0 ? 'text-success' : '' }}">{{ $row['admin_in'] > 0 ? '+' : '' }}{{ $row['admin_in'] }}</td>
                                            <td class="{{ $row['admin_out'] > 0 ? 'text-danger' : '' }}">{{ $row['admin_out'] > 0 ? '-' : '' }}{{ $row['admin_out'] }}</td>
                                            <td class="{{ $row['sales_invoice'] > 0 ? 'text-danger' : '' }}">{{ $row['sales_invoice'] > 0 ? '-' : '' }}{{ $row['sales_invoice'] }}</td>
                                            <td class="{{ $row['sales_do'] > 0 ? 'text-danger' : '' }}">{{ $row['sales_do'] > 0 ? '-' : '' }}{{ $row['sales_do'] }}</td>
                                            <td class="{{ $row['wastage'] > 0 ? 'text-danger' : '' }}">{{ $row['wastage'] > 0 ? '-' : '' }}{{ $row['wastage'] }}</td>
                                            <td class="font-weight-bold">{{ $row['closing_stock'] }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="8" class="text-center text-muted">No stock snapshot available for this trip.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Invoice Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Payment</th>
                                            <th>Item</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Price (RM)</th>
                                            <th class="text-right">Amount (RM)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoices as $invoice)
                                            @php
                                                $invoiceTotal = $invoice->invoicedetail->sum('totalprice');
                                                $paymentLabel = $paymentLabels[$invoice->paymentterm] ?? $invoice->paymentterm;
                                                $firstItem = true;
                                            @endphp
                                            @foreach($invoice->invoicedetail as $detail)
                                            <tr>
                                                @if($firstItem)
                                                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $invoice->invoiceno }}</td>
                                                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $invoice->customer?->company ?? '-' }}</td>
                                                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $paymentLabel }}</td>
                                                @php $firstItem = false; @endphp
                                                @endif
                                                <td>{{ $detail->product?->name ?? '-' }}</td>
                                                <td class="text-right">{{ $detail->quantity }}</td>
                                                <td class="text-right">{{ number_format($detail->price, 2) }}</td>
                                                <td class="text-right">{{ number_format($detail->totalprice, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold" style="background:#f8f9fa;">
                                                <td colspan="6" class="text-right">Invoice Total:</td>
                                                <td class="text-right">{{ number_format($invoiceTotal, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted">No invoices found for this trip.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($deliveryOrders->count())
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Delivery Order Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>DO No</th>
                                            <th>Customer</th>
                                            <th>Payment</th>
                                            <th>Item</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Price (RM)</th>
                                            <th class="text-right">Amount (RM)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deliveryOrders as $deliveryOrder)
                                            @php
                                                $doTotal = $deliveryOrder->deliveryorderdetail->sum('totalprice');
                                                $paymentLabel = $paymentLabels[$deliveryOrder->paymentterm] ?? $deliveryOrder->paymentterm;
                                                $firstItem = true;
                                            @endphp
                                            @foreach($deliveryOrder->deliveryorderdetail as $detail)
                                            <tr>
                                                @if($firstItem)
                                                <td rowspan="{{ $deliveryOrder->deliveryorderdetail->count() }}">{{ $deliveryOrder->dono }}</td>
                                                <td rowspan="{{ $deliveryOrder->deliveryorderdetail->count() }}">{{ $deliveryOrder->customer?->company ?? '-' }}</td>
                                                <td rowspan="{{ $deliveryOrder->deliveryorderdetail->count() }}">{{ $paymentLabel }}</td>
                                                @php $firstItem = false; @endphp
                                                @endif
                                                <td>{{ $detail->product?->name ?? '-' }}</td>
                                                <td class="text-right">{{ $detail->quantity }}</td>
                                                <td class="text-right">{{ number_format($detail->price, 2) }}</td>
                                                <td class="text-right">{{ number_format($detail->totalprice, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold" style="background:#f8f9fa;">
                                                <td colspan="6" class="text-right">DO Total:</td>
                                                <td class="text-right">{{ number_format($doTotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
@endsection
