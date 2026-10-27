@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">DO Summary</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-list-alt"></i>
                            DO Summary
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.do-summary.show') }}">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-sm-4">
                                        <label>Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" class="form-control" required>
                                            <option value="">Select customer</option>
                                            @foreach($customers as $id => $company)
                                                <option value="{{ $id }}" {{ (string) $customerId === (string) $id ? 'selected' : '' }}>{{ $company }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label>DO date from <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $dateFrom) }}" required>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label>DO date to <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $dateTo) }}" required>
                                    </div>
                                    <div class="form-group col-sm-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mb-3">
                                            <i class="fa fa-search"></i> Show
                                        </button>
                                    </div>
                                </div>
                            </form>

                            @if($orders !== null)
                                @if($orders->isEmpty())
                                    <p class="text-muted">No delivery orders for this customer in that date range.</p>
                                @else
                                    <form method="POST" action="{{ route('reports.do-summary.pdf') }}" target="_blank">
                                        @csrf
                                        <input type="hidden" name="customer_id" value="{{ $customerId }}">
                                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                                        <p class="text-muted">Tick the delivery orders to include, then export. Each selected DO is listed with its product lines.</p>
                                        <button type="submit" class="btn btn-success mb-3">
                                            <i class="fa fa-file-pdf-o"></i> Export PDF
                                        </button>
                                        @foreach($orders as $order)
                                            <div class="border rounded p-3 mb-3">
                                                <label class="mb-2">
                                                    <input type="checkbox" name="do_ids[]" value="{{ $order->id }}" checked>
                                                    <strong>{{ $order->dono ?? ('DO #'.$order->id) }}</strong>
                                                    <span class="text-muted">{{ $order->date }}</span>
                                                </label>
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th class="text-right">Qty</th>
                                                            <th class="text-right">Price (RM)</th>
                                                            <th class="text-right">Total (RM)</th>
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
                                                                <td class="text-right">{{ $line->quantity }}</td>
                                                                <td class="text-right">{{ number_format($line->price, 2) }}</td>
                                                                <td class="text-right">{{ number_format($line->totalprice, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <th colspan="3" class="text-right">DO total</th>
                                                            <th class="text-right">{{ number_format($orderTotal, 2) }}</th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    </form>
                                @endif
                            @endif

                            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    HideLoad();
});
</script>
@endpush
