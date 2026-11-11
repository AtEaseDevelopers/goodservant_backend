@extends('portal.layouts.app')

@section('title', 'Order Details')

@php
    $navActive = 'orders';
    $statusStyles = [
        0 => ['label' => 'Submitted', 'dot' => 'bg-blue-500',  'cls' => 'bg-blue-50 text-blue-700 ring-blue-600/20'],
        1 => ['label' => 'Completed', 'dot' => 'bg-green-500', 'cls' => 'bg-green-50 text-green-700 ring-green-600/20'],
        2 => ['label' => 'Cancelled', 'dot' => 'bg-slate-400', 'cls' => 'bg-slate-100 text-slate-600 ring-slate-500/20'],
    ];
    $st = $statusStyles[$order->status] ?? ['label' => '—', 'dot' => 'bg-slate-400', 'cls' => 'bg-slate-100 text-slate-600 ring-slate-500/20'];
    $lines = collect($order->salesorderdetail);
    $orderTotal = $lines->sum('totalprice');
    $orderDate = $order->getRawOriginal('date');
@endphp

@section('content')

    <a href="{{ route('portal.orders') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        Back to My Orders
    </a>

    <h1 class="mb-6 text-2xl font-bold tracking-tight text-slate-900">Order Details</h1>

    {{-- Summary --}}
    <div class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 shadow-sm sm:grid-cols-3">
        <div class="bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Order Number</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $order->sono }}</p>
        </div>
        <div class="bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Order Date</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $orderDate ? \Carbon\Carbon::parse($orderDate)->format('d M Y') : '—' }}</p>
        </div>
        <div class="bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Status</p>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $st['cls'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
            </span>
        </div>
    </div>

    {{-- Ordered products --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Ordered Products</h2>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ $lines->count() }} {{ Str::plural('item', $lines->count()) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">Product</th>
                        <th class="px-5 py-3 text-right font-medium">Qty</th>
                        <th class="px-5 py-3 text-right font-medium">Unit Price</th>
                        <th class="px-5 py-3 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lines as $line)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ optional($line->product)->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">{{ $line->quantity }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-slate-700">RM {{ number_format($line->price, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-slate-900">RM {{ number_format($line->totalprice, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">No items on this order.</td></tr>
                    @endforelse
                </tbody>
                @if($lines->count())
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td class="px-5 py-3.5 text-right text-sm font-medium text-slate-500" colspan="3">Total</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-base font-bold text-slate-900">RM {{ number_format($orderTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Documents --}}
    <h2 class="mb-3 mt-8 text-sm font-semibold text-slate-900">Documents</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Sales Order PDF --}}
        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Sales Order PDF</p>
                    <p class="text-xs text-green-600">Ready for download</p>
                </div>
            </div>
            <a href="{{ route('portal.orders.pdf', [Crypt::encrypt($order->id), 'view']) }}" target="_blank" rel="noopener"
               class="shrink-0 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
                View PDF
            </a>
        </div>

        {{-- Invoice PDF (not wired for the portal yet) --}}
        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Invoice PDF</p>
                    <p class="text-xs text-slate-400">Not available yet</p>
                </div>
            </div>
            <span class="shrink-0 cursor-not-allowed rounded-lg bg-slate-100 px-3.5 py-2 text-sm font-semibold text-slate-400">
                View PDF
            </span>
        </div>
    </div>
@endsection
