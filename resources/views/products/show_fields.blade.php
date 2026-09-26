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
    <p>{{ optional($product->productType)->name ?? '-' }}</p>
</div>


<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('products.status')) !!}:
    <p>{{ $product->status == 1 ? __('products.active') : __('products.unactive') }}</p>
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