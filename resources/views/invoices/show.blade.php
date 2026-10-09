@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoices.index') }}">{{ __('invoices.invoices') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoices.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                                  <a href="{{ route('invoices.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('invoices.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('invoices.invoice_detail') }}</strong>
                                <a class="pull-right" href="{{ route('invoices.detail', Crypt::encrypt($id)) }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             </div>
                             <div class="card-body">
                                <table class="table table-striped table-bordered dataTable" width="100%" role="grid" style="width: 100%;">
                                    <thead>
                                        <tr role="row">
                                            <th>{{ __('invoices.product') }}</th>
                                            <th>{{ __('invoices.quantity') }}</th>
                                            <th>{{ __('invoices.price') }}</th>
                                            <th>{{ __('invoices.total_price') }}</th>
                                            <th>{{ __('invoices.remark') }}</th>
                                            <th>{{ __('invoices.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($invoicedetails) == 0)
                                            <tr class="odd">
                                                <td valign="top" colspan="10" class="dataTables_empty">No matching records found</td>
                                            </tr>
                                        @endif
                                        @foreach($invoicedetails as $i=>$invoicedetail)
                                            @if( ($i+1) % 2 == 0 )

                                                <tr class="even">
                                                    <td>{{ $invoicedetail['product']['name'] ?? '-' }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ $invoicedetail['price'] }}</td>
                                                    <td>{{ $invoicedetail['totalprice'] }}</td>
                                                    <td>{{ $invoicedetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr class="odd">
                                                    <td>{{ $invoicedetail['product']['name'] ?? '-' }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ $invoicedetail['price'] }}</td>
                                                    <td>{{ $invoicedetail['totalprice'] }}</td>
                                                    <td>{{ $invoicedetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>

                                @php
                                    $discountSubtotal = \App\Support\InvoiceDiscount::subtotal($id);
                                    $discountAmount = \App\Support\InvoiceDiscount::amount($id);
                                @endphp
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered mb-0">
                                            <tr>
                                                <th style="width:50%">Subtotal</th>
                                                <td class="text-right">RM {{ number_format($discountSubtotal, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Discount</th>
                                                <td class="text-right">- RM {{ number_format($discountAmount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Total</th>
                                                <td class="text-right font-weight-bold">RM {{ number_format($discountSubtotal - $discountAmount, 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        {!! Form::open(['route' => ['invoices.discount', Crypt::encrypt($id)], 'class' => 'form-inline']) !!}
                                            <label for="discount" class="mr-2">Discount (RM)</label>
                                            <input type="number" name="discount" id="discount" class="form-control mr-2" style="width:120px" min="0" step="0.01" value="{{ number_format($discountAmount, 2, '.', '') }}">
                                            <button type="button" class="btn btn-outline-secondary mr-2" id="discount-round-down" data-cents="{{ number_format($discountSubtotal - floor($discountSubtotal), 2, '.', '') }}">Round down (RM {{ number_format($discountSubtotal - floor($discountSubtotal), 2) }})</button>
                                            <button type="submit" class="btn btn-primary">Save discount</button>
                                        {!! Form::close() !!}
                                        <small class="text-muted">Set 0 to remove the discount. "Round down" takes off the cents, e.g. RM 15.40 becomes RM 15.00.</small>
                                    </div>
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
                $('.card .card-header a')[1].click();
            }
        });
        $(document).on('click', '#discount-round-down', function() {
            $('#discount').val($(this).data('cents'));
        });
    </script>
@endpush
