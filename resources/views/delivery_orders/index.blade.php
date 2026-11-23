@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ trans('delivery_orders.delivery_orders') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ trans('delivery_orders.delivery_orders') }}
                             <a class="pull-right" href="{{ route('deliveryOrders.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="combineconvert" title="Combine the selected delivery orders (must all be from the same customer) into one invoice">
                                <i class="fa fa-object-group"></i> {{ __('Combine and Convert') }}
                             </button>
                             <button type="button" class="btn btn-success btn-sm pull-right mr-2" id="billingconvert" title="Convert all pending DOs of the weekly or monthly billing customers, one invoice per customer">
                                <i class="fa fa-calendar-check-o"></i> Billing Convert
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
                title: @json(__('Combine and Convert')),
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

        // ---- Billing Convert: weekly / monthly billing customers
        function billingDates(cycle){
            var now = new Date();
            var pad = function(n){ return (n < 10 ? '0' : '') + n; };
            var fmt = function(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
            if (cycle === 'monthly') {
                var first = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                var last = new Date(now.getFullYear(), now.getMonth(), 0);
                return [fmt(first), fmt(last)];
            }
            var day = (now.getDay() + 6) % 7; // Monday = 0
            var monday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day - 7);
            var sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 6);
            return [fmt(monday), fmt(sunday)];
        }

        function escapeHtml(s){ return $('<div>').text(s == null ? '' : s).html(); }

        $(document).on("click", "#billingconvert", function(){
            var dates = billingDates('weekly');
            var dialog = $.confirm({
                title: 'Billing Convert',
                columnClass: 'col-md-10 col-md-offset-1',
                content:
                    '<div class="form-row align-items-end">' +
                        '<div class="col-md-3 mb-2"><label class="mb-1">Billing</label>' +
                            '<select id="bill_cycle" class="form-control"><option value="weekly">Weekly Billing</option><option value="monthly">Monthly Billing</option></select></div>' +
                        '<div class="col-md-3 mb-2"><label class="mb-1">From</label><input type="date" id="bill_from" class="form-control" value="' + dates[0] + '"></div>' +
                        '<div class="col-md-3 mb-2"><label class="mb-1">To</label><input type="date" id="bill_to" class="form-control" value="' + dates[1] + '"></div>' +
                        '<div class="col-md-3 mb-2"><button type="button" class="btn btn-secondary btn-block" id="bill_load">Show DOs</button></div>' +
                    '</div>' +
                    '<p class="text-muted small mb-2">All pending DOs dated up to the "To" date are included (also older ones). Each ticked customer gets one invoice, dated the "To" date.</p>' +
                    '<div id="bill_warn"></div>' +
                    '<div id="bill_list" style="max-height: 50vh; overflow-y: auto; overflow-x: hidden;"></div>',
                buttons: {
                    convert: {
                        text: 'Convert',
                        btnClass: 'btn-success',
                        action: function(){
                            var ids = this.$content.find('.bill_pick:checked').map(function(){ return $(this).val(); }).get();
                            if (ids.length === 0) { noti('i','Info','Please tick at least one customer'); return false; }
                            var payload = {
                                cycle: this.$content.find('#bill_cycle').val(),
                                from: this.$content.find('#bill_from').val(),
                                to: this.$content.find('#bill_to').val(),
                                customer_ids: ids,
                                _token: "{{ csrf_token() }}"
                            };
                            $.confirm({
                                title: 'Confirm',
                                content: 'Create ' + ids.length + ' invoice(s) now?',
                                buttons: {
                                    Yes: function(){ runBillingConvert(payload); dialog.close(); },
                                    No: function(){}
                                }
                            });
                            return false;
                        }
                    },
                    cancel: { text: 'Close' }
                },
                onContentReady: function(){
                    var self = this;
                    var load = function(){
                        self.$content.find('#bill_list').html('<p class="text-muted">Loading...</p>');
                        $.get("{{ route('deliveryOrders.billingPreview') }}", {
                            cycle: self.$content.find('#bill_cycle').val(),
                            from: self.$content.find('#bill_from').val(),
                            to: self.$content.find('#bill_to').val()
                        }, function(res){
                            var warn = res.drivers_on_trip.length ? '<div class="alert alert-warning py-2">On trip now: ' + escapeHtml(res.drivers_on_trip.join(', ')) + '. Convert after they end their trip.</div>' : '';
                            self.$content.find('#bill_warn').html(warn);
                            if (res.rows.length === 0) {
                                self.$content.find('#bill_list').html('<p>No pending DOs for ' + (res.cycle === 'monthly' ? 'monthly' : 'weekly') + ' billing customers up to ' + res.to + '.</p>');
                                return;
                            }
                            var rows = res.rows.map(function(r){
                                return '<tr><td><input type="checkbox" class="bill_pick" value="' + r.customer_id + '" checked></td>' +
                                    '<td>' + escapeHtml(r.customer) + '</td><td>' + escapeHtml(r.driver) + '</td>' +
                                    '<td class="text-center">' + r.count + '</td><td>' + r.first_date + ' - ' + r.last_date + '</td>' +
                                    '<td class="text-right">' + r.total + '</td></tr>';
                            }).join('');
                            self.$content.find('#bill_list').html(
                                '<table class="table table-sm table-striped mb-0"><thead><tr><th><input type="checkbox" id="bill_all" checked></th>' +
                                '<th>Customer</th><th>Driver</th><th class="text-center">DOs</th><th>DO dates</th><th class="text-right">Total (RM)</th></tr></thead>' +
                                '<tbody>' + rows + '</tbody></table>');
                        }).fail(function(err){
                            self.$content.find('#bill_list').html('<p class="text-danger">' + escapeHtml((err.responseJSON && err.responseJSON.message) || 'Could not load') + '</p>');
                        });
                    };
                    this.$content.on('change', '#bill_cycle', function(){
                        var d = billingDates($(this).val());
                        self.$content.find('#bill_from').val(d[0]);
                        self.$content.find('#bill_to').val(d[1]);
                        load();
                    });
                    this.$content.on('click', '#bill_load', load);
                    this.$content.on('change', '#bill_all', function(){ self.$content.find('.bill_pick').prop('checked', this.checked); });
                    load();
                }
            });
        });

        function runBillingConvert(payload){
            ShowLoad();
            $.ajax({
                url: "{{ route('deliveryOrders.billingConvert') }}",
                type: "POST",
                data: payload,
                success: function(res){
                    HideLoad();
                    $('.buttons-reload').click();
                    var list = res.invoices.map(function(i){
                        return '<li><a href="' + i.url + '" target="_blank">' + escapeHtml(i.invoiceno) + '</a> - ' + escapeHtml(i.customer) + ' (' + i.count + ' DO)</li>';
                    }).join('');
                    var failed = res.failed.map(function(f){
                        return '<li class="text-danger">' + escapeHtml(f.customer) + ': ' + escapeHtml(f.message) + '</li>';
                    }).join('');
                    $.alert({ title: res.message, columnClass: 'col-md-8 col-md-offset-2', content: '<ul class="mb-0">' + list + failed + '</ul>' });
                },
                error: function(err){
                    HideLoad();
                    noti('e','Billing Convert', (err.responseJSON && err.responseJSON.message) || 'Failed to convert');
                }
            });
        }
    </script>
@endpush
