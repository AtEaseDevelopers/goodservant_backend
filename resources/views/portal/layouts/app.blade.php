<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Order') · Good Servant</title>
    <link rel="icon" type="image/x-icon" href="{{ config('app.url') }}/favicon.ico">

    {{-- Compiled, self-hosted Tailwind + Inter font (no external CDN).
         Rebuild after changing portal views/styles: `npm run portal-css`.
         The ?v=<mtime> busts the browser cache whenever the file changes
         (e.g. after a rebuild/deploy), so no hard refresh is needed; when it
         is unchanged the URL stays stable and the file is served from cache. --}}
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v={{ @filemtime(public_path('css/portal.css')) ?: '1' }}">
    @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
@php($navActive = $navActive ?? 'order')
@php($cartCount = (int) \App\Models\CartItem::where('customer_id', optional(Auth::guard('customer')->user())->id)->sum('quantity'))
<div class="min-h-full pb-20 md:pb-0">
    {{-- Top navigation --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-8">
                <a href="{{ route('portal.order') }}" class="text-lg font-bold tracking-tight text-slate-900">Good&nbsp;Servant</a>
                <nav class="hidden items-center gap-6 md:flex">
                    @php($linkBase = 'text-sm transition-colors')
                    <a href="{{ route('portal.order') }}"
                       class="{{ $linkBase }} {{ $navActive === 'order' ? 'font-semibold text-slate-900 underline decoration-2 underline-offset-8' : 'text-slate-500 hover:text-slate-900' }}">Order</a>
                    <a href="{{ route('portal.orders') }}"
                       class="{{ $linkBase }} {{ $navActive === 'orders' ? 'font-semibold text-slate-900 underline decoration-2 underline-offset-8' : 'text-slate-500 hover:text-slate-900' }}">My Orders</a>
                    <a href="{{ route('portal.cart') }}"
                       class="{{ $linkBase }} inline-flex items-center gap-1.5 {{ $navActive === 'cart' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-900' }}">
                        <span class="{{ $navActive === 'cart' ? 'underline decoration-2 underline-offset-8' : '' }}">Cart</span>
                        <span class="cart-count inline-flex {{ $cartCount > 0 ? '' : 'hidden' }} min-w-[1.25rem] items-center justify-center rounded-full bg-slate-900 px-1.5 py-0.5 text-center text-xs font-semibold text-white">{{ $cartCount ?: '' }}</span>
                    </a>
                </nav>
            </div>

            <div class="flex items-center gap-4">
                @if($customer ?? false)
                    <span class="hidden text-sm text-slate-400 sm:inline">{{ $customer->company }}</span>
                @endif
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-slate-500 transition-colors hover:text-slate-900">Sign Out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>
</div>

{{-- Bottom navigation (mobile only) --}}
<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white md:hidden">
    <div class="mx-auto flex max-w-6xl items-stretch justify-around">
        <a href="{{ route('portal.order') }}"
           class="flex flex-1 flex-col items-center gap-1 py-2.5 text-xs {{ $navActive === 'order' ? 'text-slate-900' : 'text-slate-400' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 12h16.5m-16.5 6.75h16.5"/></svg>
            <span class="font-medium">Order</span>
        </a>
        <a href="{{ route('portal.orders') }}"
           class="flex flex-1 flex-col items-center gap-1 py-2.5 text-xs {{ $navActive === 'orders' ? 'text-slate-900' : 'text-slate-400' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7.5 4.5h9a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5h-9a1.5 1.5 0 0 0-1.5 1.5v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
            <span class="font-medium">My Orders</span>
        </a>
        <a href="{{ route('portal.cart') }}"
           class="relative flex flex-1 flex-col items-center gap-1 py-2.5 text-xs {{ $navActive === 'cart' ? 'text-slate-900' : 'text-slate-400' }}">
            <span class="relative">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                <span class="cart-count absolute -right-2 -top-2 {{ $cartCount > 0 ? '' : 'hidden' }} min-w-[1.125rem] rounded-full bg-slate-900 px-1 py-0.5 text-center text-[0.625rem] font-semibold leading-none text-white">{{ $cartCount ?: '' }}</span>
            </span>
            <span class="font-medium">Cart</span>
        </a>
    </div>
</nav>

<script>
    // Server-backed cart. The nav badge is rendered server-side; each mutation
    // posts to the portal cart endpoints and the response carries the new count.
    window.PortalCart = (function () {
        var ADD = @json(route('portal.cart.add'));
        var UPDATE = @json(route('portal.cart.update'));
        var REMOVE = @json(route('portal.cart.remove'));
        var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function setBadge(n) {
            document.querySelectorAll('.cart-count').forEach(function (el) {
                el.textContent = n > 0 ? n : '';
                el.classList.toggle('hidden', n <= 0);
            });
        }
        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            }).then(function (r) { return r.json(); });
        }
        function apply(promise) {
            return promise.then(function (d) {
                if (d && typeof d.count !== 'undefined') { setBadge(d.count); }
                return d;
            });
        }
        function add(productId, qty) { return apply(post(ADD, { product_id: productId, quantity: qty })); }
        function update(productId, qty) { return apply(post(UPDATE, { product_id: productId, quantity: qty })); }
        function remove(productId) { return apply(post(REMOVE, { product_id: productId })); }

        return { add: add, update: update, remove: remove, setBadge: setBadge };
    })();
</script>
@stack('scripts')
</body>
</html>
