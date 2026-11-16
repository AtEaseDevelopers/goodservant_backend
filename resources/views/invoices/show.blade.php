@extends('layouts.app')

@section('content')
    @php
        $discountSubtotal = \App\Support\InvoiceDiscount::subtotal($id);
        $discountAmount = \App\Support\InvoiceDiscount::amount($id);
        $roundDownCents = $discountSubtotal - floor($discountSubtotal);
    @endphp
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoices.index') }}">{{ __('invoices.invoices') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ $invoice->invoiceno }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="card">
                     <div class="card-header d-flex align-items-center">
                         <a href="{{ route('invoices.index') }}" id="dv-back" class="btn btn-light btn-sm">
                             <i class="fa fa-arrow-left"></i> {{ __('detail.back') }}
                         </a>
                     </div>
                     <div class="card-body">
                         @include('invoices.show_fields', ['total' => $discountSubtotal - $discountAmount])

                         @include('partials.order_items', [
                             'items' => $invoicedetails,
                             'title' => __('invoices.invoice_detail'),
                             'addUrl' => route('invoices.detail', Crypt::encrypt($id)),
                             'deleteRoute' => 'invoices.deletedetail',
                             'confirm' => __('invoices.are_you_sure_to_delete_the_invoice_detail'),
                         ])

                         <div class="row">
                             <div class="col-md-5 mb-3">
                                 <div class="dv-section">
                                     <div class="dv-section-title"><i class="fa fa-calculator mr-1"></i> {{ __('detail.total') }}</div>
                                     <table class="table table-sm">
                                         <tr>
                                             <td class="pl-3">{{ __('detail.subtotal') }}</td>
                                             <td class="text-right pr-3">RM {{ number_format($discountSubtotal, 2) }}</td>
                                         </tr>
                                         <tr>
                                             <td class="pl-3">{{ __('detail.discount') }}</td>
                                             <td class="text-right pr-3 text-danger">- RM {{ number_format($discountAmount, 2) }}</td>
                                         </tr>
                                         <tr class="font-weight-bold">
                                             <td class="pl-3">{{ __('detail.total') }}</td>
                                             <td class="text-right pr-3">RM {{ number_format($discountSubtotal - $discountAmount, 2) }}</td>
                                         </tr>
                                     </table>
                                 </div>
                             </div>
                             <div class="col-md-7 mb-3">
                                 <div class="dv-section">
                                     <div class="dv-section-title"><i class="fa fa-tag mr-1"></i> {{ __('detail.discount') }}</div>
                                     <div class="p-3">
                                         {!! Form::open(['route' => ['invoices.discount', Crypt::encrypt($id)], 'class' => 'form-inline']) !!}
                                             <label for="discount" class="mr-2">{{ __('detail.discount_rm') }}</label>
                                             <input type="number" name="discount" id="discount" class="form-control form-control-sm mr-2 mb-1" style="width:120px" min="0" step="0.01" value="{{ number_format($discountAmount, 2, '.', '') }}">
                                             <button type="button" class="btn btn-sm btn-outline-secondary mr-2 mb-1" id="discount-round-down" data-cents="{{ number_format($roundDownCents, 2, '.', '') }}">{{ __('detail.round_down') }} (RM {{ number_format($roundDownCents, 2) }})</button>
                                             <button type="submit" class="btn btn-sm btn-primary mb-1">{{ __('detail.save_discount') }}</button>
                                         {!! Form::close() !!}
                                         <small class="text-muted d-block mt-1">{{ __('detail.discount_hint') }}</small>
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
            if (e.key === "Escape") { $('#dv-back')[0].click(); }
            if (e.altKey && e.keyCode == 78) { $('#dv-add-item')[0].click(); }
        });
        $(document).ready(function () { HideLoad(); });
        $(document).on('click', '#discount-round-down', function() {
            $('#discount').val($(this).data('cents'));
        });
    </script>
@endpush
