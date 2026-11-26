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

<!-- Show in driver app -->
<div class="form-group col-sm-6">
    {!! Form::label('show_in_app', 'Show in driver app') !!}
    {{ Form::select('show_in_app', [1 => 'Yes', 0 => 'No (hidden in the app, kept on products here)'], null, ['class' => 'form-control']) }}
</div>
