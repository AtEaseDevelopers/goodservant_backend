{!! Form::open(['route' => ['customers.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('customers.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('customers.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    @if(!empty($portal_login_url))
    <a href="#" class='btn btn-ghost-primary copy-portal-link'
       data-link="{{ $portal_login_url }}"
       title="Copy ordering link for this customer">
       <i class="fa fa-link"></i>
    </a>
    @endif
    <a href="#" class='btn btn-ghost-warning reset-portal-link'
       data-url="{{ $reset_portal_url }}"
       title="Reset ordering link (the old link stops working)">
       <i class="fa fa-refresh"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('customers.are_you_sure_to_delete_the_customer')."')"    

    ]) !!}
</div>
{!! Form::close() !!}
