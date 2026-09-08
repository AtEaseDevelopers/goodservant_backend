<!-- Dono Field -->
<div class="form-group">
    {!! Form::label('dono', __('delivery_orders.delivery_order_no')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->dono }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('delivery_orders.date')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->date }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('delivery_orders.customer')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->customer->company ?? '' }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('delivery_orders.driver')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->driver->name ?? '' }}</p>
</div>

<!-- Kelindan Id Field -->
<div class="form-group">
    {!! Form::label('kelindan_id', __('delivery_orders.kelindan')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->kelindan->name ?? '' }}</p>
</div>

<!-- Agent Id Field -->
<div class="form-group">
    {!! Form::label('agent_id', __('delivery_orders.agent')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->agent->name ?? '' }}</p>
</div>

<!-- Supervisor Id Field -->
<div class="form-group">
    {!! Form::label('supervisor_id', __('delivery_orders.supervisor')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->supervisor->name ?? '' }}</p>
</div>

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('delivery_orders.payment_term')) !!}:<span class="asterisk"> *</span>
    @if($deliveryOrder->paymentterm == 1)
         <p>Cash</p>
    @elseif($deliveryOrder->paymentterm == 2)
        <p>Credit</p>
    @elseif($deliveryOrder->paymentterm == 3)
        <p>Online BankIn</p>
    @elseif($deliveryOrder->paymentterm == 4)
        <p>E-wallet</p>
    @elseif($deliveryOrder->paymentterm == 5)
        <p>Cheque {{ '-' . $deliveryOrder->chequeno}}</p>
    @else
        <p>Payment Term: Unknown</p>
    @endif
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('delivery_orders.status')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->status == 1 ? "Completed" : "New" }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('delivery_orders.remark')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->remark }}</p>
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
