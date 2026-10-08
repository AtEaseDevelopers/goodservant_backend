@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('products.index') !!}">{{ __('products.products') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Arrange') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-sort fa-lg"></i>
                            <strong>{{ __('Arrange Products') }}</strong>
                            <p class="text-muted mb-0">{{ __('Drag to Reorder') }}. {{ __('The mobile app shows products in this order.') }}</p>
                        </div>
                        <div class="card-body">
                            {!! Form::open(['route' => 'products.savearrange']) !!}
                            <table class="table table-striped table-bordered" id="sortable-table" width="100%">
                                <thead>
                                    <tr>
                                        <th width="50"></th>
                                        <th width="100">{{ __('Sequence') }}</th>
                                        <th>{{ __('Code') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th width="120">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $index => $product)
                                    <tr>
                                        <td class="text-center"><span class="drag-handle">☰</span></td>
                                        <td class="sequence-cell">{{ $index + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="products[]" value="{{ $product->id }}">
                                            {{ $product->code }}
                                        </td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->productType?->name ?? '-' }}</td>
                                        <td>{{ $product->status == 1 ? 'Active' : 'Unactive' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {!! Form::submit(__('Save'), ['class' => 'btn btn-primary']) !!}
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #sortable-table tbody tr { cursor: move; }
        .sortable-placeholder { background-color: #f0f0f0; border: 2px dashed #ccc; height: 50px; }
        .drag-handle { cursor: move; color: #999; font-size: 18px; }
        .drag-handle:hover { color: #333; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });

        $(document).ready(function() {
            $("#sortable-table tbody").sortable({
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function() {
                    $('#sortable-table tbody tr').each(function(index) {
                        $(this).find('.sequence-cell').text(index + 1);
                    });
                },
                cursor: 'move',
                opacity: 0.6,
                placeholder: 'sortable-placeholder',
                tolerance: 'pointer'
            }).disableSelection();

            HideLoad();
        });
    </script>
@endpush
