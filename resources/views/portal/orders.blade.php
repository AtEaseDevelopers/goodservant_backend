@extends('portal.layouts.app')

@section('title', 'My Orders')
@php($navActive = 'orders')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">My Orders</h1>
        <p class="mt-1 text-sm text-slate-500">View your orders and track their status.</p>
    </div>

    {{-- Search + filters --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
            </span>
            <input id="order-search" type="text" autocomplete="off" value="{{ $q }}" placeholder="Search by order number or product"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        </div>

        {{-- Date filter --}}
        <div class="relative">
            <select id="date-filter"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm font-medium text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 sm:w-auto">
                <option value="" {{ $date === '' ? 'selected' : '' }}>All Dates</option>
                <option value="7d" {{ $date === '7d' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30d" {{ $date === '30d' ? 'selected' : '' }}>Last 30 days</option>
                <option value="month" {{ $date === 'month' ? 'selected' : '' }}>This month</option>
                <option value="3m" {{ $date === '3m' ? 'selected' : '' }}>Last 3 months</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </span>
        </div>

        {{-- Status filter --}}
        <div class="relative">
            <select id="status-filter"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm font-medium text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 sm:w-auto">
                <option value="" {{ $status === '' ? 'selected' : '' }}>All Statuses</option>
                <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </span>
        </div>
    </div>

    {{-- Orders table. First batch server-rendered; more rows load on scroll. --}}
    <div id="orders-table-wrap" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">Order No.</th>
                        <th class="px-5 py-3 font-medium">Order Date</th>
                        <th class="hidden px-5 py-3 font-medium md:table-cell">Products</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="orders-list"
                       data-endpoint="{{ route('portal.orders.feed') }}"
                       data-next-page="2"
                       data-has-more="{{ $hasMore ? '1' : '0' }}"
                       data-status="{{ $status }}"
                       data-q="{{ $q }}"
                       data-date="{{ $date }}"
                       class="divide-y divide-slate-100">
                    @include('portal._orders', ['orders' => $orders])
                </tbody>
            </table>
        </div>
    </div>

    {{-- Infinite-scroll sentinel + spinner --}}
    <div id="load-sentinel" class="h-8"></div>
    <div id="load-spinner" class="hidden items-center justify-center py-6 text-slate-400">
        <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Empty state --}}
    <div id="no-orders" class="hidden py-14 text-center">
        <svg class="mx-auto h-40 w-40" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No orders">
            {{-- soft backdrop --}}
            <circle cx="100" cy="100" r="80" fill="#f1f5f9"/>
            <circle cx="100" cy="100" r="80" stroke="#e2e8f0" stroke-width="2" stroke-dasharray="4 7"/>
            {{-- order document --}}
            <g transform="rotate(-6 100 100)">
                <rect x="62" y="52" width="76" height="96" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="2.5"/>
                {{-- avatar + heading lines --}}
                <circle cx="80" cy="74" r="8" fill="#e2e8f0"/>
                <rect x="94" y="69" width="30" height="5" rx="2.5" fill="#cbd5e1"/>
                <rect x="94" y="79" width="20" height="5" rx="2.5" fill="#e2e8f0"/>
                {{-- list rows --}}
                <rect x="74" y="100" width="52" height="5" rx="2.5" fill="#e2e8f0"/>
                <rect x="74" y="112" width="40" height="5" rx="2.5" fill="#e2e8f0"/>
                <rect x="74" y="124" width="46" height="5" rx="2.5" fill="#e2e8f0"/>
            </g>
            {{-- floating "add" badge --}}
            <g transform="translate(128 118)">
                <circle cx="16" cy="16" r="17" fill="#0f172a"/>
                <path d="M16 9v14M9 16h14" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
            </g>
        </svg>
        <h3 class="mt-5 text-base font-semibold text-slate-900">No orders to show</h3>
        <p class="mx-auto mt-1 max-w-xs text-sm text-slate-500">When you place an order it will appear here, ready to track and reorder.</p>
        <a href="{{ route('portal.order') }}"
           class="mt-6 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12A1.125 1.125 0 0 1 19.743 21H4.257a1.125 1.125 0 0 1-1.125-1.243l1.264-12A1.125 1.125 0 0 1 5.513 6.75h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
            Browse products
        </a>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var list = document.getElementById('orders-list');
        var tableWrap = document.getElementById('orders-table-wrap');
        var sentinel = document.getElementById('load-sentinel');
        var spinner = document.getElementById('load-spinner');
        var empty = document.getElementById('no-orders');
        var statusFilter = document.getElementById('status-filter');
        var dateFilter = document.getElementById('date-filter');
        var search = document.getElementById('order-search');
        var endpoint = list.dataset.endpoint;

        var currentStatus = list.dataset.status || '';
        var currentDate = list.dataset.date || '';
        var currentQ = list.dataset.q || '';
        var nextPage = parseInt(list.dataset.nextPage, 10) || 2;
        var hasMore = list.dataset.hasMore === '1';
        var loading = false;
        var reqToken = 0;

        function trailingDebounce(fn, wait) {
            var timer = null;
            return function () {
                var ctx = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () { fn.apply(ctx, args); }, wait);
            };
        }

        function queryString(page) {
            var p = ['page=' + page];
            if (currentStatus) { p.push('status=' + encodeURIComponent(currentStatus)); }
            if (currentDate) { p.push('date=' + encodeURIComponent(currentDate)); }
            if (currentQ) { p.push('q=' + encodeURIComponent(currentQ)); }
            return p.join('&');
        }

        function updateEmptyState() {
            var has = !!list.querySelector('.order-row');
            empty.classList.toggle('hidden', loading || has);
            tableWrap.classList.toggle('hidden', !loading && !has);
        }

        // Row click navigates to the order (the explicit "View" link still works).
        list.addEventListener('click', function (e) {
            if (e.target.closest('a')) { return; }
            var row = e.target.closest('.order-row');
            if (row && row.dataset.href) { window.location = row.dataset.href; }
        });

        function isSentinelVisible() {
            return sentinel.getBoundingClientRect().top < (window.innerHeight + 400);
        }

        function showSpinner(on) {
            spinner.classList.toggle('hidden', !on);
            spinner.classList.toggle('flex', on);
        }

        function loadMore() {
            if (loading || !hasMore) { return; }
            loading = true;
            showSpinner(true);

            var token = reqToken;
            var url = endpoint + '?' + queryString(nextPage);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (token !== reqToken) { return; }
                    list.insertAdjacentHTML('beforeend', data.html);
                    hasMore = data.hasMore;
                    nextPage = data.nextPage;
                    loading = false;
                    showSpinner(false);
                    updateEmptyState();
                    if (hasMore && isSentinelVisible()) { loadMore(); }
                })
                .catch(function () {
                    if (token !== reqToken) { return; }
                    loading = false;
                    showSpinner(false);
                });
        }

        // Re-run the list from page 1 whenever a filter or the search changes.
        function reload() {
            reqToken++;
            nextPage = 1;
            hasMore = true;
            loading = false;
            list.innerHTML = '';
            showSpinner(false);
            empty.classList.add('hidden');
            tableWrap.classList.remove('hidden');
            // Keep the URL shareable/refresh-safe.
            var qs = queryString(1).replace(/^page=1&?/, '');
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
            loadMore();
        }

        statusFilter.addEventListener('change', function () { currentStatus = statusFilter.value; reload(); });
        dateFilter.addEventListener('change', function () { currentDate = dateFilter.value; reload(); });
        search.addEventListener('input', trailingDebounce(function () { currentQ = search.value.trim(); reload(); }, 300));

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) { loadMore(); }
            }, { rootMargin: '400px' }).observe(sentinel);
        } else {
            window.addEventListener('scroll', function () {
                if (isSentinelVisible()) { loadMore(); }
            });
        }

        updateEmptyState();
    })();
</script>
@endpush
