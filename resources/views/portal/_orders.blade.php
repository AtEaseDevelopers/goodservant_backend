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
        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">
            {{ $order->sono }}
            @if($order->off_day)
                <span class="mt-1 flex w-fit items-center gap-1 rounded-md border border-blue-200 bg-blue-600/5 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                    </svg>
                    Off day
                </span>
            @endif
        </td>
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
