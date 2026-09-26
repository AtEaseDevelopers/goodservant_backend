<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name') !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'required']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status') !!}
    {{ Form::select('status', [1 => 'Active', 0 => 'Inactive'], null, ['class' => 'form-control']) }}
</div>
