@extends('portal.layouts.app')

@section('title', 'Cart')
@php($navActive = 'cart')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Cart</h1>
        <p class="mt-1 text-sm text-slate-500">Review your products and quantities before submitting your order.</p>
    </div>

    {{-- Empty --}}
    <div id="cart-empty" class="{{ $items->isEmpty() ? '' : 'hidden' }} py-14 text-center">
        <svg class="mx-auto h-40 w-40" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Empty cart">
            <circle cx="100" cy="100" r="80" fill="#f1f5f9"/>
            <circle cx="100" cy="100" r="80" stroke="#e2e8f0" stroke-width="2" stroke-dasharray="4 7"/>
            <path d="M68 76h70l-7 46a10 10 0 0 1-9.9 8.5H84.9A10 10 0 0 1 75 122l-7-46Z" fill="#ffffff" stroke="#cbd5e1" stroke-width="2.5" stroke-linejoin="round"/>
            <path d="M82 76a18 18 0 0 1 36 0" stroke="#cbd5e1" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="90" cy="150" r="7" fill="#e2e8f0"/>
            <circle cx="124" cy="150" r="7" fill="#e2e8f0"/>
        </svg>
        <h3 class="mt-5 text-base font-semibold text-slate-900">Your cart is empty</h3>
        <p class="mx-auto mt-1 max-w-xs text-sm text-slate-500">Add products from the Order page and they will show up here.</p>
        <a href="{{ route('portal.order') }}"
           class="mt-6 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
            Browse products
        </a>
    </div>

    {{-- Cart --}}
    <div id="cart-wrap" class="{{ $items->isEmpty() ? 'hidden' : '' }}">
        {{-- Off day: order is placed but not delivered today --}}
        <label for="off-day"
               class="group mb-4 flex cursor-pointer items-center gap-4 rounded-xl border-2 border-slate-200 bg-white p-4 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500 has-[:focus-visible]:ring-offset-2">
            <input id="off-day" type="checkbox" class="sr-only">

            {{-- Icon badge --}}
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 transition-colors group-has-[:checked]:bg-blue-600 group-has-[:checked]:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                </svg>
            </span>

            {{-- Copy --}}
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-slate-900">Off day — don't deliver today</span>
                <span class="mt-0.5 block text-sm text-slate-500">Submit this order but skip today's delivery. It will be delivered the next day.</span>
            </span>

            {{-- Toggle switch --}}
            <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-200 transition-colors group-has-[:checked]:bg-blue-600">
                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform group-has-[:checked]:translate-x-5"></span>
            </span>
        </label>

        <div id="cart-items" class="space-y-3">
            @foreach($items as $item)
                @include('portal._cart_row', ['item' => $item])
            @endforeach
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('portal.order') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Continue Shopping
            </a>
            <button id="submit-order" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                <svg id="submit-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span>Submit Order</span>
            </button>
        </div>
    </div>

    {{-- Error toast --}}
    <div id="cart-toast"
         class="pointer-events-none fixed bottom-24 left-1/2 z-30 -translate-x-1/2 translate-y-2 rounded-full bg-red-600 px-4 py-2 text-sm font-medium text-white opacity-0 shadow-lg transition-all duration-200 md:bottom-6">
    </div>

    {{-- Custom confirm dialog --}}
    <div id="confirm-modal" class="pointer-events-none fixed inset-0 z-40 flex items-center justify-center p-4 opacity-0 transition-opacity duration-150">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-confirm-cancel></div>
        <div id="confirm-dialog" class="relative w-full max-w-sm scale-95 rounded-2xl bg-white p-6 text-center shadow-xl transition-transform duration-150">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-900">Remove item?</h3>
            <p id="confirm-message" class="mt-1 text-sm text-slate-500"></p>
            <div class="mt-6 flex gap-3">
                <button type="button" data-confirm-cancel
                        class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">Cancel</button>
                <button type="button" id="confirm-ok"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700">Remove</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var SUBMIT_URL = @json(route('portal.cart.submit'));
        var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        var itemsEl = document.getElementById('cart-items');
        var wrap = document.getElementById('cart-wrap');
        var emptyEl = document.getElementById('cart-empty');
        var submitBtn = document.getElementById('submit-order');
        var submitSpinner = document.getElementById('submit-spinner');
        var offDayEl = document.getElementById('off-day');
        var toast = document.getElementById('cart-toast');
        var toastTimer;

        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.remove('opacity-0', 'translate-y-2');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { toast.classList.add('opacity-0', 'translate-y-2'); }, 2500);
        }
        function renderEmpty() {
            wrap.classList.add('hidden');
            emptyEl.classList.remove('hidden');
        }

        // --- Custom confirm dialog ---
        var modal = document.getElementById('confirm-modal');
        var dialog = document.getElementById('confirm-dialog');
        var confirmMessage = document.getElementById('confirm-message');
        var confirmOk = document.getElementById('confirm-ok');
        var pendingConfirm = null;
        function openConfirm(message, onConfirm) {
            confirmMessage.textContent = message;
            pendingConfirm = onConfirm;
            modal.classList.remove('opacity-0', 'pointer-events-none');
            dialog.classList.remove('scale-95');
            dialog.classList.add('scale-100');
        }
        function closeConfirm() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            dialog.classList.add('scale-95');
            dialog.classList.remove('scale-100');
            pendingConfirm = null;
        }
        confirmOk.addEventListener('click', function () { var fn = pendingConfirm; closeConfirm(); if (fn) { fn(); } });
        modal.querySelectorAll('[data-confirm-cancel]').forEach(function (el) { el.addEventListener('click', closeConfirm); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('pointer-events-none')) { closeConfirm(); }
        });

        // --- Quantity changes (debounced per product) ---
        var timers = {};
        function commitQty(productId, qty) {
            clearTimeout(timers[productId]);
            timers[productId] = setTimeout(function () { window.PortalCart.update(productId, qty); }, 300);
        }

        itemsEl.addEventListener('click', function (e) {
            var stepBtn = e.target.closest('.qty-dec, .qty-inc');
            if (stepBtn) {
                var row = stepBtn.closest('.cart-row');
                var input = row.querySelector('.qty-input');
                var v = (parseInt(input.value, 10) || 1) + (stepBtn.classList.contains('qty-inc') ? 1 : -1);
                if (v < 1) { v = 1; }
                input.value = v;
                commitQty(row.dataset.id, v);
                return;
            }
            var rm = e.target.closest('.remove-btn');
            if (rm) {
                var row2 = rm.closest('.cart-row');
                openConfirm('Remove "' + (rm.dataset.name || 'this item') + '" from your cart?', function () {
                    window.PortalCart.remove(row2.dataset.id).then(function () {
                        row2.remove();
                        if (!itemsEl.querySelector('.cart-row')) { renderEmpty(); }
                    });
                });
            }
        });
        itemsEl.addEventListener('input', function (e) {
            var input = e.target.closest('.qty-input');
            if (input) {
                var cleaned = input.value.replace(/\D+/g, '');
                if (cleaned !== input.value) { input.value = cleaned; }
            }
        });
        itemsEl.addEventListener('change', function (e) {
            var input = e.target.closest('.qty-input');
            if (input) {
                var row = input.closest('.cart-row');
                var v = parseInt(input.value, 10);
                if (!v || v < 1) { v = 1; }
                input.value = v;
                commitQty(row.dataset.id, v);
            }
        });

        // --- Submit ---
        submitBtn.addEventListener('click', function () {
            submitBtn.disabled = true;
            submitSpinner.classList.remove('hidden');
            fetch(SUBMIT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ off_day: offDayEl ? offDayEl.checked : false })
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (res.ok && res.data.ok) {
                        window.PortalCart.setBadge(0);
                        window.location = res.data.redirect;
                    } else {
                        submitBtn.disabled = false;
                        submitSpinner.classList.add('hidden');
                        showToast(res.data.message || 'Could not submit your order.');
                    }
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    submitSpinner.classList.add('hidden');
                    showToast('Could not submit your order. Please try again.');
                });
        });
    })();
</script>
@endpush
