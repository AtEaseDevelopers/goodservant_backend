@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('trips.index') }}">{{ __('trips.trips') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('trips.end_trip_summary') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="card">
                     <div class="card-header d-flex align-items-center">
                         <a href="{{ route('trips.index') }}" id="dv-back" class="btn btn-light btn-sm">
                             <i class="fa fa-arrow-left"></i> {{ __('detail.back') }}
                         </a>
                         <strong class="ml-2">{{ __('trips.end_trip_summary') }}</strong>
                     </div>
                     <div class="card-body">
                         @include('trips.show_fields')

                         @php
                             $tables = [
                                 ['fa-shopping-cart', __('trips.product_sold'), $trip->productsold['details'] ?? [], __('trips.no_product_sold'), true],
                                 ['fa-gift', __('trips.product_foc'), $trip->productfoc['details'] ?? [], __('trips.no_product_foc'), false],
                                 ['fa-trash-o', __('trips.wastage'), $trip->wastage ?? [], __('trips.no_wastage'), false],
                             ];
                         @endphp
                         <div class="row mt-2">
                             @foreach($tables as [$icon, $title, $rows, $empty, $withPrice])
                                 <div class="col-lg-4 mb-3"><div class="dv-section">
                                     <div class="dv-section-title"><i class="fa {{ $icon }} mr-1"></i> {{ $title }}</div>
                                     <table class="table table-sm table-hover">
                                         <thead class="thead-light">
                                             <tr>
                                                 <th class="pl-3">{{ __('trips.product') }}</th>
                                                 <th class="text-right {{ $withPrice ? '' : 'pr-3' }}">{{ __('trips.quantity') }}</th>
                                                 @if($withPrice)<th class="text-right pr-3">{{ __('trips.price') }}</th>@endif
                                             </tr>
                                         </thead>
                                         <tbody>
                                         @forelse($rows as $row)
                                             <tr>
                                                 <td class="pl-3">{{ $row->name }}</td>
                                                 <td class="text-right {{ $withPrice ? '' : 'pr-3' }}">{{ $row->quantity }}</td>
                                                 @if($withPrice)<td class="text-right pr-3">{{ is_numeric($row->price) ? number_format($row->price, 2) : $row->price }}</td>@endif
                                             </tr>
                                         @empty
                                             <tr><td colspan="3" class="text-center text-muted py-3">{{ $empty }}</td></tr>
                                         @endforelse
                                         </tbody>
                                     </table>
                                 </div></div>
                             @endforeach
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@include('partials.detail_scripts')
