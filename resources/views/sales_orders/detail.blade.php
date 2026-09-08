@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('salesOrders.index') !!}">Sales Orders</a>
      </li>
      <li class="breadcrumb-item active">Detail</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Sales Order Detail</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => ['salesOrders.adddetail',Crypt::encrypt($id)]]) !!}

                                    <!-- Product Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('product_id', 'Product:') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('product_id', $productItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Product...','autofocus']) !!}
                                    </div>


                                    <!-- Quantity Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('quantity', 'Quantity:') !!}<span class="asterisk"> *</span>
                                        {!! Form::number('quantity', null, ['class' => 'form-control','min' => 0,'step' => 1]) !!}
                                    </div>

                                    <!-- Price Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('price', 'Price:') !!}<span class="asterisk"> *</span>
                                        {!! Form::text('price', null, ['class' => 'form-control','min' => 0,'step' => 0.01]) !!}
                                    </div>

                                    <!-- Remark Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('remark', 'Remark:') !!}
                                        {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
                                    </div>

                                    <!-- Submit Field -->
                                    <div class="form-group col-sm-12">
                                        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                        <a href="{{ route('salesOrders.show',Crypt::encrypt($id)) }}" class="btn btn-secondary">Cancel</a>
                                    </div>

                                    @push('scripts')
                                        <script>
                                            $(document).keyup(function(e) {
                                                if (e.key === "Escape") {
                                                    $('form a.btn-secondary')[0].click();
                                                }
                                            });
                                            $(document).ready(function () {
                                                HideLoad();
                                            });
                                            $("#product_id").change(function(){
                                                getprice();
                                            });
                                            function getprice(){
                                                var salesorder_id = {{ $id }};
                                                var product_id = $('#product_id').val();
                                                if(salesorder_id != '' && product_id != ''){
                                                    ShowLoad();
                                                    var url = '{{ config("app.url") }}/salesOrders/getprice/'+salesorder_id+'/'+product_id;
                                                    $.get(url, function(data, status){
                                                        if(status == 'success'){
                                                            if(data.status){
                                                                $('#price').val(data.data);
                                                            }else{
                                                                noti('e','Please contact your administrator',data.message);
                                                            }
                                                            HideLoad();
                                                        }else{
                                                            noti('e','Please contact your administrator','')
                                                            HideLoad();
                                                        }
                                                    });

                                                }
                                            }
                                        </script>
                                    @endpush

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
