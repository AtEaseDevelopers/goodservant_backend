@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Sales Orders</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Sales Orders
                             <a class="pull-right" href="{{ route('salesOrders.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <button type="button" class="btn btn-success btn-sm pull-right mr-2" id="convert" title="Convert the selected Sales Order into a Delivery Order or an Invoice">
                                <i class="fa fa-exchange"></i> Convert
                             </button>
                         </div>
                         <div class="card-body">
                             @include('sales_orders.table')
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
                url: "{{ url('/salesOrders/massdestroy') }}",
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

        $(document).on("click", "#convert", function(e){
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select one Sales Order to convert');
                return;
            }
            if(window.checkboxid.length > 1){
                noti('i','Info','Please select only one Sales Order at a time to convert');
                return;
            }

            $.confirm({
                title: 'Convert Sales Order',
                content: `
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select id="convert_paymentterm" class="form-control">
                            <option value="1">Cash</option>
                            <option value="2">Credit</option>
                            <option value="3">Online BankIn</option>
                            <option value="4">E-wallet</option>
                            <option value="5">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group" id="convert_cheque_container" style="display:none;">
                        <label>Cheque No</label>
                        <input type="text" id="convert_chequeno" class="form-control">
                    </div>
                `,
                onContentReady: function () {
                    var jc = this;
                    jc.$content.find('#convert_paymentterm').on('change', function(){
                        if($(this).val() == '5'){
                            jc.$content.find('#convert_cheque_container').show();
                        }else{
                            jc.$content.find('#convert_cheque_container').hide();
                        }
                    });
                },
                buttons: {
                    Convert: function() {
                        var paymentterm = this.$content.find('#convert_paymentterm').val();
                        var chequeno = this.$content.find('#convert_chequeno').val();
                        convertSalesOrder(window.checkboxid[0], paymentterm, chequeno);
                    },
                    Cancel: function() {
                        return;
                    }
                }
            });
        });

        function convertSalesOrder(id, paymentterm, chequeno){
            ShowLoad();
            $.ajax({
                url: "{{ url('/salesOrders/convert') }}",
                type:"POST",
                data:{
                    id: id,
                    paymentterm: paymentterm,
                    chequeno: chequeno,
                    _token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Converted', response.message);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to convert Sales Order');
                }
            });
        }
    </script>
@endpush
