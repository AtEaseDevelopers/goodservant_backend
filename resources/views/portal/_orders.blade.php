@php
    $statusStyles = [
        0 => ['label' => 'Submitted', 'dot' => 'bg-blue-500',  'cls' => 'bg-blue-50 text-blue-700 ring-blue-600/20'],
        1 => ['label' => 'Completed', 'dot' => 'bg-green-500', 'cls' => 'bg-green-50 text-green-700 ring-green-600/20'],
        2 => ['label' => 'Cancelled', 'dot' => 'bg-slate-400', 'cls' => 'bg-slate-100 text-slate-600 ring-slate-500/20'],
    ];
@endphp
@foreach($orders as $order)
    @php
        $st = $statusStyles[$order->status] ?? ['label' => '—', 'dot' => 'bg-slate-400', 'cls' => 'bg-slate-100 text-slate-600 ring-slate-500/20'];
        $names = collect($order->salesorderdetail)->map(fn ($d) => optional($d->product)->name)->filter()->values();
        $summary = $names->take(2)->implode(', ');
        $extra = $names->count() - 2;
        if ($extra > 0) { $summary .= ' +' . $extra . ' more'; }
        $orderDate = $order->getRawOriginal('date');
        $href = route('portal.orders.show', Crypt::encrypt($order->id));
    @endphp
    <tr class="order-row cursor-pointer transition-colors hover:bg-slate-50" data-href="{{ $href }}">
        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $order->sono }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $orderDate ? \Carbon\Carbon::parse($orderDate)->format('d M Y') : '—' }}</td>
        <td class="hidden max-w-xs px-5 py-4 text-slate-600 md:table-cell">
            <span class="line-clamp-1">{{ $summary !== '' ? $summary : '—' }}</span>
        </td>
        <td class="whitespace-nowrap px-5 py-4">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $st['cls'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
            </span>
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-right">
            <a href="{{ $href }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
                View
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        </td>
    </tr>
@endforeach
