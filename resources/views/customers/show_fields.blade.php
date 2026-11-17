@php
    $dash = '—';
    $paymentterm = [
        1 => __('customers.payment_term_cash'),
        2 => __('customers.payment_term_bankin'),
        3 => __('customers.payment_term_credit_note'),
    ][$customer->paymentterm] ?? $dash;
    $groups = array_filter(array_map('trim', explode(',', (string) ($customer->group ?? ''))));
    $addressLine2 = trim(implode(' ', array_filter([$customer->postcode ?? null, $customer->city ?? null])));
    $addressLine3 = implode(', ', array_filter([$customer->state ?? null, $customer->country ?? null]));
    $yesNo = fn($on) => $on
        ? '<span class="badge badge-success">' . e(__('customers.yes')) . '</span>'
        : '<span class="badge badge-secondary">' . e(__('customers.no')) . '</span>';
@endphp

@include('partials.detail_styles')

{{-- Header: who this customer is --}}
<div class="d-flex flex-wrap align-items-start justify-content-between mb-3">
    <div>
        <h2 class="dv-title">
            {{ $customer->company ?: $dash }}
            <span class="badge badge-light border align-middle ml-1">{{ $customer->code }}</span>
            @if($customer->status == 1)
                <span class="badge badge-success align-middle">{{ __('customers.active') }}</span>
            @else
                <span class="badge badge-secondary align-middle">{{ __('customers.unactive') }}</span>
            @endif
        </h2>
        @if($customer->second_description || ($customer->chinese_name ?? null))
            <div class="dv-sub">
                {{ implode(' · ', array_filter([$customer->second_description, $customer->chinese_name ?? null])) }}
            </div>
        @endif
    </div>
</div>

{{-- Key facts at a glance --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="dv-fact">
            <div class="dv-fact-label">{{ __('customers.payment_term') }}</div>
            <div class="dv-fact-value">{{ $paymentterm }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="dv-fact">
            <div class="dv-fact-label">{{ __('customers.price_category') }}</div>
            <div class="dv-fact-value">{{ $priceCategory ?? __('customers.base_price') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="dv-fact">
            <div class="dv-fact-label">{{ __('customers.do_customer') }}</div>
            <div class="dv-fact-value">{!! $yesNo($customer->is_do_customer) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="dv-fact">
            <div class="dv-fact-label">{{ __('customers.off_day') }}</div>
            <div class="dv-fact-value">{!! $yesNo($customer->off_day) !!}</div>
        </div>
    </div>
</div>

{{-- Details, grouped --}}
<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="dv-section">
            <div class="dv-section-title"><i class="fa fa-address-card-o mr-1"></i> {{ __('customers.contact_details') }}</div>
            <dl>
                <dt>{{ __('customers.phone') }}</dt>
                <dd>{{ $customer->phone ?: $dash }}</dd>
                <dt>{{ __('customers.email') }}</dt>
                <dd>{{ ($customer->email ?? null) ?: $dash }}</dd>
                <dt>{{ __('customers.address') }}</dt>
                <dd>
                    @if($customer->address || $addressLine2 || $addressLine3)
                        {{ $customer->address }}
                        @if($addressLine2)<br>{{ $addressLine2 }}@endif
                        @if($addressLine3)<br>{{ $addressLine3 }}@endif
                    @else
                        {{ $dash }}
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="dv-section">
            <div class="dv-section-title"><i class="fa fa-truck mr-1"></i> {{ __('customers.sales_details') }}</div>
            <dl>
                <dt>{{ __('customers.group') }}</dt>
                <dd>
                    @forelse($groups as $g)
                        <span class="badge badge-info mr-1">{{ $g }}</span>
                    @empty
                        {{ $dash }}
                    @endforelse
                </dd>
                <dt>{{ __('customers.so_default_driver') }}</dt>
                <dd>{{ $customer->soDefaultDriver->name ?? $dash }}</dd>
                @if($customer->agent->name ?? null)
                    <dt>{{ __('customers.agent') }}</dt>
                    <dd>{{ $customer->agent->name }}</dd>
                @endif
                @if($customer->supervisor->name ?? null)
                    <dt>{{ __('customers.operation') }}</dt>
                    <dd>{{ $customer->supervisor->name }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="dv-section">
            <div class="dv-section-title"><i class="fa fa-file-text-o mr-1"></i> {{ __('customers.tax_details') }}</div>
            <dl>
                <dt>{{ __('customers.tin') }}</dt>
                <dd>{{ $customer->tin ?: $dash }}</dd>
                <dt>{{ __('customers.sst') }}</dt>
                <dd>{{ $customer->sst ?: $dash }}</dd>
                <dt>{{ __('customers.registration_no') }}</dt>
                <dd>{{ ($customer->registration_no ?? null) ?: $dash }}</dd>
                <dt>{{ __('customers.debtor_type') }}</dt>
                <dd>{{ $customer->debtor_type ?: $dash }}</dd>
                <dt>{{ __('customers.credit_term') }}</dt>
                <dd>{{ $customer->credit_term ?: $dash }}</dd>
            </dl>
        </div>
    </div>
</div>

{{-- Pricing for this customer (read-only) --}}
<div class="dv-section">
    <div class="dv-section-title d-flex justify-content-between align-items-center">
        <span><i class="fa fa-tags mr-1"></i> {{ __('customers.pricing_for_customer') }}</span>
        <span class="text-muted font-weight-normal text-none" style="text-transform:none;letter-spacing:0">
            {{ empty($priceCategory) ? __('customers.no_price_category') : __('customers.pricing_hint') }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 dv-price-table">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3">{{ __('customers.code') }}</th>
                    <th>{{ __('customers.product') }}</th>
                    <th class="text-right">{{ __('customers.base_price') }}</th>
                    <th class="text-right">{{ $priceCategory ? e($priceCategory) : __('customers.category_price') }}</th>
                    <th class="text-right pr-3">{{ __('customers.price_applied') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($priceList as $item)
                <tr>
                    <td class="pl-3 text-muted">{{ $item->code }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-right">{{ number_format($item->base_price, 2) }}</td>
                    <td class="text-right">{{ is_null($item->category_price) ? $dash : number_format($item->category_price, 2) }}</td>
                    <td class="text-right pr-3">
                        <strong>{{ number_format($item->effective_price, 2) }}</strong>
                        @if(!is_null($item->category_price))
                            <span class="badge badge-primary ml-1">{{ __('customers.price_source_category') }}</span>
                        @else
                            <span class="badge badge-light border ml-1">{{ __('customers.price_source_base') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('customers.no_active_products') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
