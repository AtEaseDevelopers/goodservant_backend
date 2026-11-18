{!! Form::open(['route' => ['salesOrders.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('salesorder.print', ['id' => encrypt($id), 'function' => 'view'] ) }}" class='btn btn-ghost-primary' target="_blank">
       <i class="fa fa-print"></i>
    </a>
    <a href="{{ route('salesOrders.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('salesOrders.edit', encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    @if(!empty($off_day) && !empty($editable))
        <a href="#"
           class='btn btn-ghost-warning'
           title="Make normal (remove off-day hold)"
           onclick="event.preventDefault(); if (confirm('Remove the off-day hold? This Sales Order becomes a normal, deliverable order.')) { document.getElementById('remove-off-day-{{ $id }}').submit(); }">
            <i class="fa fa-calendar-check-o"></i>
        </a>
    @endif
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('sales_orders.are_you_sure_to_delete_the_sales_order')."')"
    ]) !!}
</div>
{!! Form::close() !!}
@if(!empty($off_day) && !empty($editable))
    {!! Form::open(['route' => ['salesOrders.remove-off-day', encrypt($id)], 'method' => 'post', 'id' => 'remove-off-day-'.$id, 'style' => 'display:none']) !!}
    {!! Form::close() !!}
@endif
