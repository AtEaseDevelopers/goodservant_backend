@include('partials.detail_styles')
@php
    $types = $product->productTypes->pluck('name')->filter();
    if ($types->isEmpty() && optional($product->productType)->name) {
        $types = collect([$product->productType->name]);
    }
@endphp
<div class="mb-3">
    <h2 class="dv-title">
        {{ $product->name }}
        <span class="badge badge-light border align-middle ml-1">{{ $product->code }}</span>
        @include('partials.dv_status', ['active' => $product->status == 1])
    </h2>
</div>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.base_price') }}</div>
        <div class="dv-fact-value">RM {{ number_format($product->price, 2) }}</div>
    </div></div>
    <div class="col-6 col-md-5 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('products.type') }}</div>
        <div class="dv-fact-value">
            @forelse($types as $t)<span class="badge badge-info mr-1">{{ $t }}</span>@empty — @endforelse
        </div>
    </div></div>
    @einvoice
    <div class="col-12 col-md-4 mb-2"><div class="dv-fact">
        <div class="dv-fact-label">{{ __('detail.classification_code') }}</div>
        <div class="dv-fact-value">{{ $product->classification_code ? $product->classification_code . ' - ' . (App\Models\ClassificationCode::getDescription($product->classification_code) ?? '') : '—' }}</div>
    </div></div>
    @endeinvoice
</div>
<div class="dv-section">
    <div class="dv-section-title"><i class="fa fa-tags mr-1"></i> {{ __('detail.price_by_category') }}</div>
    <table class="table table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th class="pl-3">{{ __('detail.price_category') }}</th>
                <th class="text-right pr-3">{{ __('detail.price') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse(($priceCategories ?? []) as $category)
            <tr>
                <td class="pl-3">{{ $category->name }}</td>
                <td class="text-right pr-3">
                    @if(is_null($category->category_price))
                        {{ number_format($product->price, 2) }} <span class="badge badge-light border ml-1">{{ __('detail.base') }}</span>
                    @else
                        <strong>{{ number_format($category->category_price, 2) }}</strong>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="2" class="text-center text-muted py-3">{{ __('detail.no_price_categories') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.detail_scripts')
