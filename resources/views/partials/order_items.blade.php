{{--
    Line items for invoices, sales orders and delivery orders.
    Params: $items (array of detail rows with product), $title, $addUrl, $deleteRoute (route name), $confirm (text)
--}}
<div class="dv-section mb-3">
    <div class="dv-section-title d-flex justify-content-between align-items-center">
        <span><i class="fa fa-list mr-1"></i> {{ $title }}</span>
        <a href="{{ $addUrl }}" id="dv-add-item" class="btn btn-sm btn-outline-primary" title="Alt+N">
            <i class="fa fa-plus"></i> {{ __('detail.add_item') }}
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3">{{ __('detail.product') }}</th>
                    <th class="text-right">{{ __('detail.quantity') }}</th>
                    <th class="text-right">{{ __('detail.price') }}</th>
                    <th class="text-right">{{ __('detail.line_total') }}</th>
                    <th>{{ __('detail.remark') }}</th>
                    <th class="text-right pr-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td class="pl-3">{{ $item['product']['name'] ?? '—' }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format((float) $item['price'], 2) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format((float) $item['totalprice'], 2) }}</td>
                    <td class="text-muted">{{ $item['remark'] }}</td>
                    <td class="text-right pr-3">
                        {!! Form::open(['route' => [$deleteRoute, Crypt::encrypt($item['id'])], 'method' => 'delete', 'class' => 'd-inline']) !!}
                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                'type' => 'submit',
                                'class' => 'btn btn-sm btn-ghost-danger',
                                'title' => __('detail.delete'),
                                'onclick' => 'return confirm(' . e(json_encode($confirm)) . ')',
                            ]) !!}
                        {!! Form::close() !!}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('detail.no_items') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
