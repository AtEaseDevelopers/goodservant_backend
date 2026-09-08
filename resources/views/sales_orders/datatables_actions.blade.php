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
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('sales_orders.are_you_sure_to_delete_the_sales_order')."')"
    ]) !!}
</div>
{!! Form::close() !!}
