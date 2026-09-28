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

            var soId = window.checkboxid[0];
            ShowLoad();
            $.ajax({
                url: "{{ url('/salesOrders') }}/" + soId + "/convertitems",
                type: "GET",
                success: function(response){
                    HideLoad();
                    openConvertDialog(soId, response.items || []);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to load Sales Order items');
                }
            });
        });

        function openConvertDialog(soId, items){
            var rows = items.map(function(item){
                var total = (parseFloat(item.quantity) * parseFloat(item.price)).toFixed(2);
                return `
                    <tr data-detail-id="${item.sales_order_detail_id}" data-price="${item.price}">
                        <td>${item.product_name || ''}</td>
                        <td>${parseFloat(item.price).toFixed(2)}</td>
                        <td><input type="number" min="0.01" step="0.01" class="form-control convert-qty" value="${item.quantity}"></td>
                        <td class="convert-line-total">${total}</td>
                    </tr>
                `;
            }).join('');

            $.confirm({
                title: 'Convert Sales Order',
                content: `
                    <table class="table table-sm table-bordered mb-3">
                        <thead>
                            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr>
                        </thead>
                        <tbody id="convert_items_body">
                            ${rows}
                        </tbody>
                    </table>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select id="convert_paymentterm" class="form-control">
                            <option value="1">Cash</option>
                            <option value="2">Credit</option>
                            <option value="3">Online Banking (QR Code)</option>
                            <option value="4">E-wallet</option>
                        </select>
                    </div>
                `,
                onContentReady: function() {
                    this.$content.on('input', '.convert-qty', function(){
                        var row = $(this).closest('tr');
                        var price = parseFloat(row.data('price')) || 0;
                        var qty = parseFloat($(this).val()) || 0;
                        row.find('.convert-line-total').text((price * qty).toFixed(2));
                    });
                },
                buttons: {
                    Convert: function() {
                        var paymentterm = this.$content.find('#convert_paymentterm').val();
                        var convertItems = [];
                        var hasInvalidQty = false;
                        this.$content.find('#convert_items_body tr').each(function(){
                            var qty = parseFloat($(this).find('.convert-qty').val());
                            if (!qty || qty <= 0) {
                                hasInvalidQty = true;
                            }
                            convertItems.push({
                                sales_order_detail_id: $(this).data('detail-id'),
                                quantity: qty
                            });
                        });
                        if (hasInvalidQty) {
                            noti('i','Info','Every item quantity must be greater than 0');
                            return false;
                        }
                        convertSalesOrder(soId, paymentterm, null, convertItems);
                    },
                    Cancel: function() {
                        return;
                    }
                }
            });
        }

        function convertSalesOrder(id, paymentterm, chequeno, items){
            ShowLoad();
            $.ajax({
                url: "{{ url('/salesOrders/convert') }}",
                type:"POST",
                data:{
                    id: id,
                    paymentterm: paymentterm,
                    chequeno: chequeno,
                    items: items,
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
