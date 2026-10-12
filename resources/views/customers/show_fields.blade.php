<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', __('customers.code')) !!}:
    <p>{{ $customer->code }}</p>
</div>

<!-- Company Field -->
<div class="form-group">
    {!! Form::label('company', __('customers.company')) !!}:
    <p>{{ $customer->company }}</p>
</div>

@php
    if($customer->paymentterm == 1){
        $paymentterm = __('customers.payment_term_cash');
    }
    if($customer->paymentterm == 2){
        $paymentterm = __('customers.payment_term_bankin');
    }
    if($customer->paymentterm == 3){
        $paymentterm = __('customers.payment_term_credit_note');
    }
@endphp

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('customers.payment_term')) !!}:
    <p>{{ $paymentterm }}</p>
</div>

<!-- Is DO Customer Field -->
<div class="form-group">
    {!! Form::label('is_do_customer', __('customers.is_do_customer')) !!}:
    <p>{{ $customer->is_do_customer ? 'Yes' : 'No' }}</p>
</div>

<!-- Group Field -->
<div class="form-group">
    {!! Form::label('group', __('customers.group')) !!}:
    <p>{{ $customer->group ?? '' }}</p>
</div>

<!-- Agent Id Field -->
<div class="form-group">
    {!! Form::label('agent_id', __('customers.agent')) !!}:
    <p>{{ $customer->agent->name ?? '' }}</p>
</div>

<!-- Supervisor Id Field -->
<div class="form-group">
    {!! Form::label('supervisor_id', __('customers.operation')) !!}:
    <p>{{ $customer->supervisor->name ?? '' }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', __('customers.phone')) !!}:
    <p>{{ $customer->phone }}</p>
</div>

<!-- Address Field -->
<div class="form-group">
    {!! Form::label('address', __('customers.address')) !!}:
    <p>{{ $customer->address }}</p>
</div>

<!-- Sst Field -->
<div class="form-group">
    {!! Form::label('sst', __('customers.sst')) !!}:
    <p>{{ $customer->sst }}</p>
</div>

<!-- Tin Field -->
<div class="form-group">
    {!! Form::label('tin', __('customers.tin')) !!}:
    <p>{{ $customer->tin }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('customers.status')) !!}:
    <p>{{ $customer->status == 1 ? __('customers.active') : __('customers.unactive') }}</p>
</div>

<!-- Price Category Field -->
<div class="form-group">
    {!! Form::label('product_price_category_id', 'Price Category') !!}:
    <p>{{ $priceCategory ?? '—' }}</p>
</div>

<!-- Price list for this customer (read-only) -->
<div class="form-group">
    {!! Form::label('price_list', 'Pricing for this customer') !!}
    @if(empty($priceCategory))
        <p class="text-muted mb-2">No price category assigned — every product uses its base price.</p>
    @endif
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Code</th>
                <th>Product</th>
                <th class="text-right">Base Price</th>
                <th class="text-right">{{ $priceCategory ? e($priceCategory).' Price' : 'Category Price' }}</th>
                <th class="text-right">Price Applied</th>
            </tr>
        </thead>
        <tbody>
        @forelse($priceList as $item)
            <tr>
                <td>{{ $item->code }}</td>
                <td>{{ $item->name }}</td>
                <td class="text-right">{{ number_format($item->base_price, 2) }}</td>
                <td class="text-right">{{ is_null($item->category_price) ? '—' : number_format($item->category_price, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($item->effective_price, 2) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No active products.</td></tr>
        @endforelse
        </tbody>
    </table>
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