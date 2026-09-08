@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Delivery Orders</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Delivery Orders
                             <a class="pull-right" href="{{ route('deliveryOrders.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="combineconvert" title="Combine the selected delivery orders (must all be from the same customer) into one invoice">
                                <i class="fa fa-object-group"></i> Combine and Convert
                             </button>
                         </div>
                         <div class="card-body">
                             @include('delivery_orders.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            }
        });

        $(document).on("click", "#massdelete", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to delete 1 row!"
            }else{
                m = "Confirm to delete " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Delete',
                content: m,
                buttons: {
                    Yes: function() {
                        massdelete(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/massdestroy') }}",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Delete Successfully',response+' row(s) had been deleted.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        $(document).on("click", "#combineconvert", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one delivery order');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to convert 1 delivery order into an invoice?"
            }else{
                m = "Confirm to combine " + window.checkboxid.length + " delivery orders (must be from the same customer) into one invoice?"
            }
            $.confirm({
                title: 'Combine and Convert',
                content: m,
                buttons: {
                    Yes: function() {
                        combineConvertDo(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });

        function combineConvertDo(ids){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/combine-convert') }}",
                type:"POST",
                data:{
                    ids: ids,
                    _token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Converted', response.message);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to convert Delivery Orders');
                }
            });
        }
    </script>
@endpush
