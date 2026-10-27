<!-- Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('code', __('products.code')) !!}<span class="asterisk"> *</span>
    {!! Form::text('code', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('products.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- Image Field -->
<div class="form-group col-sm-6">
    {!! Form::label('image', 'Image') !!}
    <div class="mb-2">
        <img id="imagePreview" src="{{ !empty($product) ? $product->image_path : '' }}" alt="Preview" style="max-height: 100px; max-width: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; {{ (!empty($product) && $product->image_path) ? '' : 'display: none;' }}">
    </div>
    {!! Form::file('image', ['class' => 'form-control-file', 'id' => 'imageInput']) !!}
    <small class="form-text text-muted">JPEG, PNG or GIF, max 2MB.</small>
    <div class="text-danger small" id="imageTypeError"></div>
</div>

<!-- Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('price', __('products.price')) !!}<span class="asterisk"> *</span>
    {!! Form::number('price', null, ['class' => 'form-control', 'step' => '0.01']) !!}
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    @php
        $selectedTypeIds = [];
        if (!empty($product)) {
            $selectedTypeIds = $product->productTypes()->pluck('product_types.id')->all();
            if ($selectedTypeIds === [] && $product->type_id) {
                $selectedTypeIds = [(int) $product->type_id];
            }
        }
        $typeOptions = \App\Models\ProductType::query()
            ->where(function ($query) use ($selectedTypeIds) {
                $query->where('status', 1);
                if ($selectedTypeIds !== []) {
                    $query->orWhereIn('id', $selectedTypeIds);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    @endphp
    {!! Form::label('type_ids', __('products.type')) !!}<span class="asterisk"> *</span>
    {!! Form::select('type_ids[]', $typeOptions, old('type_ids', $selectedTypeIds), ['class' => 'selectpicker form-control', 'multiple' => true, 'data-live-search' => 'true', 'title' => 'Select product types']) !!}
    <small class="form-text text-muted">Select one or more. <a href="{{ route('productTypes.index') }}">Manage product types</a></small>
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('products.status')) !!}
    {{ Form::select('status', [
        1 => __('products.active'),
        0 => __('products.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>
@einvoice
<!-- Classification Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('classification_code', 'Classification Code:') !!}<span class="asterisk"> *</span>
    {!! Form::select('classification_code', $classificationOptions ?? [], null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-size' => '10', 'data-dropup-auto' => 'false', 'id' => 'classification_code', 'placeholder' => 'Select Classification Code...']) !!}
</div>
@endeinvoice
<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('products.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.cancel') }}</a>
</div>

@push('scripts')

   <style>
        #classification_code + .dropdown-menu,
        select[name="classification_code"] + .dropdown-menu,
        .bootstrap-select.show .dropdown-menu {
            max-height: 400px !important;
            overflow-y: auto !important;
            max-width: 400px !important;
            width: auto !important;
        }
        .bootstrap-select .dropdown-menu.inner {
            max-height: 350px !important;
            overflow-y: auto !important;
        }
        .bootstrap-select button {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            if ($.fn.selectpicker) {
                $('.selectpicker').selectpicker({ size: 10 });
            }
            HideLoad();
        });
        document.getElementById('imageInput').addEventListener('change', function(e) {
            var file = e.target.files[0];
            var preview = document.getElementById('imagePreview');
            var errorEl = document.getElementById('imageTypeError');
            errorEl.textContent = '';
            if (file) {
                if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                    errorEl.textContent = file.name + ' is not a JPEG, PNG or GIF - please pick an image file.';
                    preview.style.display = 'none';
                    e.target.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = '';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endpush