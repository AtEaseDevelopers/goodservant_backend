@extends('portal.layouts.app')

@section('title', 'Order')
@php($navActive = 'order')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Order</h1>
        <p class="mt-1 text-sm text-slate-500">Browse products and add them to your cart.</p>
    </div>

    {{-- Search --}}
    <div class="relative mb-6 max-w-xl">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
        </span>
        <input id="product-search" type="text" autocomplete="off" placeholder="Search products"
               class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
    </div>

    {{-- Product grid. First batch is server-rendered; more load on scroll. --}}
    <div id="product-grid"
         data-endpoint="{{ route('portal.products') }}"
         data-next-page="2"
         data-has-more="{{ $hasMore ? '1' : '0' }}"
         class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
        @include('portal._products', ['products' => $products])
    </div>

    {{-- Infinite-scroll sentinel + loading spinner --}}
    <div id="load-sentinel" class="h-8"></div>
    <div id="load-spinner" class="hidden items-center justify-center py-6 text-slate-400">
        <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Empty state --}}
    <p id="no-results" class="hidden py-12 text-center text-sm text-slate-500">No products found.</p>

    {{-- Added-to-cart toast --}}
    <div id="cart-toast"
         class="pointer-events-none fixed bottom-24 left-1/2 z-30 -translate-x-1/2 translate-y-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white opacity-0 shadow-lg transition-all duration-200 md:bottom-6">
        Added to cart
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var grid = document.getElementById('product-grid');
        var sentinel = document.getElementById('load-sentinel');
        var spinner = document.getElementById('load-spinner');
        var noResults = document.getElementById('no-results');
        var search = document.getElementById('product-search');
        var endpoint = grid.dataset.endpoint;

        // --- Infinite-scroll state ---
        var currentQ = '';
        var nextPage = parseInt(grid.dataset.nextPage, 10) || 2;
        var hasMore = grid.dataset.hasMore === '1';
        var loading = false;
        var reqToken = 0; // guards against out-of-order responses when searching

        function trailingDebounce(fn, wait) {
            var timer = null;
            return function () {
                var ctx = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () { fn.apply(ctx, args); }, wait);
            };
        }

        function updateEmptyState() {
            if (!loading && !grid.querySelector('.product-card')) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        function loadMore() {
            if (loading || !hasMore) { return; }
            loading = true;
            spinner.classList.remove('hidden');
            spinner.classList.add('flex');

            var token = reqToken;
            var url = endpoint + '?page=' + nextPage + '&q=' + encodeURIComponent(currentQ);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (token !== reqToken) { return; } // a newer search superseded this
                    grid.insertAdjacentHTML('beforeend', data.html);
                    hasMore = data.hasMore;
                    nextPage = data.nextPage;
                    loading = false;
                    spinner.classList.add('hidden');
                    spinner.classList.remove('flex');
                    updateEmptyState();
                    // If the page is still short (sentinel in view), keep filling.
                    if (hasMore && isSentinelVisible()) { loadMore(); }
                })
                .catch(function () {
                    if (token !== reqToken) { return; }
                    loading = false;
                    spinner.classList.add('hidden');
                    spinner.classList.remove('flex');
                });
        }

        function isSentinelVisible() {
            var rect = sentinel.getBoundingClientRect();
            return rect.top < (window.innerHeight + 400);
        }

        function resetForSearch(q) {
            reqToken++;        // invalidate any in-flight request
            currentQ = q;
            nextPage = 1;
            hasMore = true;
            loading = false;
            grid.innerHTML = '';
            spinner.classList.add('hidden');
            spinner.classList.remove('flex');
            noResults.classList.add('hidden');
            loadMore();
        }

        search.addEventListener('input', trailingDebounce(function () {
            resetForSearch(search.value.trim());
        }, 300));

        // Observe the sentinel; load the next batch as it approaches the viewport.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) { loadMore(); }
            }, { rootMargin: '400px' }).observe(sentinel);
        } else {
            window.addEventListener('scroll', function () {
                if (isSentinelVisible()) { loadMore(); }
            });
        }

        // --- Product card interactions (event-delegated so appended cards work) ---

        // Quantity steppers
        grid.addEventListener('click', function (e) {
            var step = e.target.closest('.qty-step');
            if (step) {
                var input = step.parentElement.querySelector('.qty-input');
                var val = (parseInt(input.value, 10) || 1) + parseInt(step.dataset.step, 10);
                input.value = Math.max(1, val);
                return;
            }
            var add = e.target.closest('.add-to-cart');
            if (add) { handleAdd(add); }
        });
        // Keep the quantity field digits-only while typing.
        grid.addEventListener('input', function (e) {
            var input = e.target.closest('.qty-input');
            if (input) {
                var cleaned = input.value.replace(/\D+/g, '');
                if (cleaned !== input.value) { input.value = cleaned; }
            }
        });
        // Clamp to at least 1 once the field loses focus / changes.
        grid.addEventListener('change', function (e) {
            var input = e.target.closest('.qty-input');
            if (input) {
                var val = parseInt(input.value, 10);
                input.value = (!val || val < 1) ? 1 : val;
            }
        });

        // Add to cart
        var toast = document.getElementById('cart-toast');
        var toastTimer;
        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.remove('opacity-0', 'translate-y-2');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toast.classList.add('opacity-0', 'translate-y-2');
            }, 1500);
        }
        function handleAdd(btn) {
            if (btn.dataset.cooldown) { return; } // ignore rapid double-clicks
            btn.dataset.cooldown = '1';
            setTimeout(function () { delete btn.dataset.cooldown; }, 500);

            var card = btn.closest('.product-card');
            var qty = parseInt(card.querySelector('.qty-input').value, 10) || 1;
            window.PortalCart.add(btn.dataset.id, qty, btn.dataset.name);
            card.querySelector('.qty-input').value = 1;
            showToast(qty + '× ' + btn.dataset.name + ' added to cart');
        }

        updateEmptyState();
    })();
</script>
@endpush
