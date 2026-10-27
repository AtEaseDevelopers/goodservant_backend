<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', __('products.code')) !!}:
    <p>{{ $product->code }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('products.name')) !!}:
    <p>{{ $product->name }}</p>
</div>

<!-- Price Field -->
<div class="form-group">
    {!! Form::label('price', __('products.price')) !!}:
    <p>{{ number_format($product->price, 2) }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', __('products.type')) !!}:
    <p>{{ $product->productTypes->pluck('name')->filter()->implode(', ') ?: (optional($product->productType)->name ?? '-') }}</p>
</div>


<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('products.status')) !!}:
    <p>{{ $product->status == 1 ? __('products.active') : __('products.unactive') }}</p>
</div>

<!-- Price per category (read-only) -->
<div class="form-group">
    {!! Form::label('price_categories', 'Price by Category') !!}
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Price Category</th>
                <th class="text-right">Price</th>
            </tr>
        </thead>
        <tbody>
        @forelse(($priceCategories ?? []) as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td class="text-right">
                    @if(is_null($category->category_price))
                        {{ number_format($product->price, 2) }} <span class="text-muted">(base)</span>
                    @else
                        {{ number_format($category->category_price, 2) }}
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="2" class="text-center text-muted">No price categories.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@einvoice
<!-- Classification Code Field -->
<div class="form-group">
    {!! Form::label('classification_code', 'Classification Code:') !!}
    <p>{{ $product->classification_code ? $product->classification_code . ' - ' . (App\Models\ClassificationCode::getDescription($product->classification_code) ?? '') : '' }}</p>
</div>
@endeinvoice

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