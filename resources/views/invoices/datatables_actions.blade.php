@if($model->doc_type === 'do')
{!! Form::open(['route' => ['deliveryOrders.destroy', encrypt($model->id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('deliveryorder.print', ['id' => encrypt($model->id), 'function' => 'view'] ) }}" class='btn btn-ghost-primary' target="_blank">
       <i class="fa fa-print"></i>
    </a>
    <a href="{{ route('deliveryOrders.show', encrypt($model->id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('deliveryOrders.edit', encrypt($model->id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('invoices.are_you_sure_to_delete_the_invoice')."')"
    ]) !!}
</div>
{!! Form::close() !!}
@else
{!! Form::open(['route' => ['invoices.destroy', encrypt($model->id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('invoice.print', ['id' => encrypt($model->id), 'function' => 'view'] ) }}" class='btn btn-ghost-primary' target="_blank">
       <i class="fa fa-print"></i>
    </a>
    <a href="{{ route('invoices.show', encrypt($model->id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('invoices.edit', encrypt($model->id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
      @noeinvoice
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('invoices.are_you_sure_to_delete_the_invoice')."')"
    ]) !!}
    @endnoeinvoice
</div>
{!! Form::close() !!}
@endif
