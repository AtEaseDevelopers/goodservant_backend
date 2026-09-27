@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('invoices.invoices') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('invoices.invoices') }}
                             <a class="pull-right" href="{{ route('invoices.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="combineconvert" title="Combine the selected Delivery Orders (must all be from the same customer) into one invoice">
                                <i class="fa fa-object-group"></i> Combine and Convert
                             </button>
                            @noeinvoice
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                            @endnoeinvoice
                             <a class="pull-right text-success pr-2" id="massactive" href="#" alt="Mass active"><i class="fa fa-check fa-lg"></i></a>
                             <!--<a class="pull-right pr-2" id="masssyncxero" href="#" alt="Mass Sync to Xero"><i class="fa fa-refresh fa-lg"></i></a>-->
                            @einvoice
                             <button type="button" class="btn btn-primary btn-sm pull-right mr-2" onclick="submitEinvoice()" title="Submit E-Invoice">
                                <i class="fa fa-file-text-o"></i> Submit E-Invoice
                            </button>
                            <button type="button" class="btn btn-info btn-sm pull-right mr-2" onclick="submitConsolidatedEinvoice()" title="Submit Consolidated E-Invoice">
                                <i class="fa fa-files-o"></i> Submit Consolidated E-Invoice
                            </button>
                            @endeinvoice
                         </div>
                         <div class="card-body">
                             @include('invoices.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
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

        // window.checkboxid values are "invoice:<id>" or "do:<id>" since this
        // table lists both document types. Actions that only apply to one
        // type filter down to it and strip the prefix before hitting the API.
        function idsByPrefix(prefix){
            return window.checkboxid
                .filter(function(id){ return id.indexOf(prefix + ':') === 0; })
                .map(function(id){ return id.substring(prefix.length + 1); });
        }

        $(document).on("click", "#combineconvert", function(e){
            var ids = idsByPrefix('do');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Delivery Order row');
                return;
            }
            var m = ids.length == 1
                ? "Confirm to convert 1 delivery order into an invoice?"
                : "Confirm to combine " + ids.length + " delivery orders (must be from the same customer) into one invoice?";
            $.confirm({
                title: 'Combine and Convert',
                content: m,
                buttons: {
                    Yes: function() {
                        combineConvertDo(ids);
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

        $(document).on("click", "#masssave", function(e){
            var m = "";
            var ids = idsByPrefix('invoice');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Invoice row');
                return;
            }else if(ids.length == 1){
                m = "Confirm to save 1 row"
            }else{
                m = "Confirm to save " + ids.length + " rows!"
            }
            $.confirm({
                title: 'Save View',
                content: m,
                buttons: {
                    Yes: function() {
                        masssave(ids);
                    },
                    No: function() {
                        return;
                    }
                }
            });
            
        });

        function masssave(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/invoices/masssave",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    toastr.success('Please find Save View ID: '+response, 'Save Successfully', {showEasing: "swing", hideEasing: "linear", showMethod: "fadeIn", hideMethod: "fadeOut", positionClass: "toast-bottom-right", timeOut: 0, allowHtml: true });
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
        
        $(document).on("click", "#massdelete", function(e){
            var m = "";
            var ids = idsByPrefix('invoice');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Invoice row');
                return;
            }else if(ids.length == 1){
                m = "Confirm to delete 1 row!"
            }else{
                m = "Confirm to delete " + ids.length + " rows!"
            }
            $.confirm({
                title: 'Mass Delete',
                content: m,
                buttons: {
                    Yes: function() {
                        massdelete(ids);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });

        $(document).on("click", "#massactive", function(e){
            var m = "";
            var ids = idsByPrefix('invoice');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Invoice row');
                return;
            }else if(ids.length == 1){
                m = "Confirm to update 1 row"
            }else{
                m = "Confirm to update " + ids.length + " rows!"
            }
            $.confirm({
                title: 'Mass Update',
                content: m,
                buttons: {
                    Completed: function() {
                        massupdatestatus(ids,1);
                    },
                    New: function() {
                        massupdatestatus(ids,0);
                    },
                    somethingElse: {
                        text: 'Cancel',
                        btnClass: 'btn-gray',
                        keys: ['enter', 'shift']
                    }
                }
            });
            
        });
        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/invoices/massdestroy",
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
        function massupdatestatus(ids,status){
            ShowLoad();
            $.ajax({
                url: "{{ url('/invoices/massupdatestatus') }}",
                type:"POST",
                data:{
                ids: ids,
                status: status
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Update Successfully',response+' row(s) had been updated.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        function submitEinvoice(){
            var ids = idsByPrefix('invoice');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Invoice row');
                return;
            }

            var invoices = [];
            var currencyRate = null;

            $.confirm({
                title: 'Submit E-Invoice',
                content: `
                    <p>Selected invoices: ` + ids.length + `</p>
                `,
                buttons: {
                    Submit: function() {
                        ids.forEach(function(id) {
                            invoices.push({
                                id: parseInt(id),
                                with_sg_gst: false
                            });
                        });

                        submitEinvoiceRequest(invoices, currencyRate);
                    },
                    Cancel: function() {
                        return;
                    }
                }
            });
        }

        function submitConsolidatedEinvoice(){
            var ids = idsByPrefix('invoice');
            if(ids.length == 0){
                noti('i','Info','Please select at least one Invoice row');
                return;
            }

            $.confirm({
                title: 'Submit Consolidated E-Invoice',
                content: `
                    <p>Selected invoices: ` + ids.length + `</p>
                `,
                buttons: {
                    Submit: function() {
                        var currencyRate = null;

                        submitConsolidatedEinvoiceRequest(ids, currencyRate);
                    },
                    Cancel: function() {
                        return;
                    }
                }
            });
        }

        function submitEinvoiceRequest(invoices, currencyRate){
            ShowLoad();
            $.ajax({
                url: "{{ url('/einvoices/submit') }}",
                type: "POST",
                data: {
                    invoices: invoices,
                    currencyRate: currencyRate,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response){
                    HideLoad();
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    if(response.success){
                        var msg = 'E-Invoices submitted successfully!<br>';
                        if(response.results.successful.length > 0){
                            msg += 'Successful: ' + response.results.successful.length + '<br>';
                        }
                        if(response.results.failed.length > 0){
                            msg += 'Failed: ' + response.results.failed.length + '<br>';
                        }
                        noti('s','Success', msg);
                    }
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Error', error.responseJSON?.message || 'Failed to submit e-invoices');
                }
            });
        }

        function submitConsolidatedEinvoiceRequest(invoiceIds, currencyRate){
            ShowLoad();
            $.ajax({
                url: "{{ url('/einvoices/submit-consolidated') }}",
                type: "POST",
                data: {
                    invoices: invoiceIds,
                    currencyRate: currencyRate,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response){
                    HideLoad();
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    if(response.success){
                        noti('s','Success', 'Consolidated E-Invoice submitted successfully!');
                    }
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Error', error.responseJSON?.message || 'Failed to submit consolidated e-invoice');
                }
            });
        }
    </script>
@endpush