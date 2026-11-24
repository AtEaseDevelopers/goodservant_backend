@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('report.reports') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-bar-chart"></i>
                            {{ __('report.reports') }}
                        </div>
                        <div class="card-body">
                            <div class="row mt-2">

                                {{-- Daily Sales Report --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #007bff;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#007bff;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-file-text-o fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('Daily Sales Report') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('View sales summary by date range. Filter by driver, customer and payment type. Includes invoice details and payment breakdown.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.daily-sales') }}" class="btn btn-primary btn-sm">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Commission --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #6f42c1;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#6f42c1;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-percent fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('report.commission') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('Driver and kelindan commission for a date range, from each person\'s rate and the sales on their invoices.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.commission') }}" class="btn btn-sm text-white" style="background:#6f42c1;">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Packing List --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #28a745;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#28a745;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-truck fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('Packing List') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('Generate a driver\'s packing list for a given date - customers by task sequence, quantity per product.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.packing-list') }}" class="btn btn-success btn-sm">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Inventory Balance --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #20c997;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#20c997;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-cubes fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('Inventory Balance') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('The stock each driver still had on the lorry when they ended their trip, trip by trip.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.inventory-balance') }}" class="btn btn-sm text-white" style="background:#20c997;">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- DO Summary --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #fd7e14;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#fd7e14;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-list-alt fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('DO Summary') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('Pick a customer and the delivery-order dates, then export the selected DOs as one report of every product line.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.do-summary') }}" class="btn btn-sm text-white" style="background:#fd7e14;">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Payment Collection Report --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #17a2b8;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#17a2b8;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-money fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">{{ __('Payment Collection Report') }}</h5>
                                            </div>
                                            <p class="text-muted small">
                                                {{ __('All payments collected for a day or date range - the invoice each payment belongs to, the driver who collected it, the payment method and amount.') }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.payment-collection') }}" class="btn btn-info btn-sm">
                                                <i class="fa fa-arrow-right"></i> {{ __('Open Report') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            </div>
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
