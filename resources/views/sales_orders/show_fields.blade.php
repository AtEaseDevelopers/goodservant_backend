<!-- Sono Field -->
<div class="form-group">
    {!! Form::label('sono', __('sales_orders.sales_order_no')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->sono }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('sales_orders.date')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->date }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('sales_orders.customer')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->customer->company ?? '' }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('sales_orders.driver')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->driver->name ?? '' }}</p>
</div>

<!-- Kelindan Id Field -->
<div class="form-group">
    {!! Form::label('kelindan_id', __('sales_orders.kelindan')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->kelindan->name ?? '' }}</p>
</div>

<!-- Agent Id Field -->
<div class="form-group">
    {!! Form::label('agent_id', __('sales_orders.agent')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->agent->name ?? '' }}</p>
</div>

<!-- Supervisor Id Field -->
<div class="form-group">
    {!! Form::label('supervisor_id', __('sales_orders.supervisor')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->supervisor->name ?? '' }}</p>
</div>

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('sales_orders.payment_term')) !!}:<span class="asterisk"> *</span>
    @if($salesOrder->paymentterm == 1)
         <p>Cash</p>
    @elseif($salesOrder->paymentterm == 2)
        <p>Credit</p>
    @elseif($salesOrder->paymentterm == 3)
        <p>Online BankIn</p>
    @elseif($salesOrder->paymentterm == 4)
        <p>E-wallet</p>
    @elseif($salesOrder->paymentterm == 5)
        <p>Cheque {{ '-' . $salesOrder->chequeno}}</p>
    @else
        <p>-</p>
    @endif
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('sales_orders.status')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->status == 1 ? "Completed" : "New" }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('sales_orders.remark')) !!}:<span class="asterisk"> *</span>
    <p>{{ $salesOrder->remark }}</p>
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
