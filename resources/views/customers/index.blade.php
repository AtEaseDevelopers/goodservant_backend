@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('customers.customers')}}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('customers.customers')}}
                            {{-- Create customer is hidden. Customers come from AutoCount. --}}
                            <!--<a class="pull-right pr-2" id="masssyncxero" href="#" alt="Mass Sync to Xero"><i class="fa fa-refresh fa-lg"></i></a>-->
                         </div>
                         <div class="card-body">
                             @include('customers.table')
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

        // Copy a customer's passwordless ordering link to the clipboard so it can
        // be sent to the real customer.
        $(document).on("click", ".copy-portal-link", function(e){
            e.preventDefault();
            var link = $(this).data('link');
            function done(){ noti('s','Copied','Ordering link copied to clipboard'); }
            function fail(){ window.prompt('Copy this ordering link:', link); }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(done, fail);
            } else {
                fail();
            }
        });

        // Rotate a customer's ordering token so any previously shared link stops
        // working. The next copied link uses the new token.
        $(document).on("click", ".reset-portal-link", function(e){
            e.preventDefault();
            var url = $(this).data('url');
            $.confirm({
                title: 'Reset ordering link',
                content: 'The current ordering link for this customer will stop working. Continue?',
                buttons: {
                    Yes: function() {
                        ShowLoad();
                        $.ajax({
                            url: url,
                            type: "POST",
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(){
                                $('.buttons-reload').click();
                                noti('s','Link reset','Old ordering link disabled. Copy the new link to share it.');
                            },
                            error: function(error){
                                HideLoad();
                                noti('e','Please contact your administrator', error.responseJSON ? error.responseJSON.message : 'Could not reset the link.');
                            }
                        });
                    },
                    No: function(){}
                }
            });
        });

        $(document).on("click", "#masssave", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to save 1 row"
            }else{
                m = "Confirm to save " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Save View',
                content: m,
                buttons: {
                    Yes: function() {
                        masssave(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
            
        });
        $(document).on("click", "#masssyncxero", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to sync 1 row!"
            }else{
                m = "Confirm to sync " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Sync to Xero',
                content: m,
                buttons: {
                    Yes: function() {
                        let url = "{{config('app.url')}}/customers/sync-xero"
                        url = `${url}?ids=${window.checkboxid}`

                        window.location.href = url
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
                url: "{{config('app.url')}}/customers/masssave",
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
        
        $(document).on("click", "#massactive", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to update 1 row"
            }else{
                m = "Confirm to update " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Update',
                content: m,
                buttons: {
                    Active: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    Unactive: function() {
                        massupdatestatus(window.checkboxid,0);
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
                url: "{{config('app.url')}}/customers/massdestroy",
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
                url: "{{config('app.url')}}/customers/massupdatestatus",
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
    </script>
@endpush

