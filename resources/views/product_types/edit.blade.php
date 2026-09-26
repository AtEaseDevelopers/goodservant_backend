@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('productTypes.index') }}">Product Types</a></li>
        <li class="breadcrumb-item">Edit</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> Edit Product Type
                        </div>
                        <div class="card-body">
                            {!! Form::model($productType, ['route' => ['productTypes.update', Crypt::encrypt($productType->id)], 'method' => 'put']) !!}
                            <div class="row">
                                @include('product_types.fields')
                            </div>
                            <div class="form-group">
                                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                <a href="{{ route('productTypes.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
