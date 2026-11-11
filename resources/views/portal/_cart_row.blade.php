<div class="cart-row flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md sm:p-4" data-id="{{ $item->product_id }}">
    <img src="{{ $item->product->image_url ?: asset('img/product-placeholder.svg') }}"
         alt="{{ $item->product->name }}" loading="lazy"
         onerror="this.onerror=null;this.src='{{ asset('img/product-placeholder.svg') }}';"
         class="h-16 w-16 shrink-0 rounded-xl bg-slate-100 object-cover ring-1 ring-inset ring-slate-900/5 sm:h-20 sm:w-20">

    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold leading-snug text-slate-900 line-clamp-2 sm:text-base">{{ $item->product->name }}</p>
    </div>

    <div class="flex shrink-0 items-center gap-3 sm:gap-5">
        <div class="flex items-center rounded-lg border border-slate-200">
            <button type="button" class="qty-dec flex h-9 w-9 items-center justify-center rounded-l-lg text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-900" aria-label="Decrease quantity">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14"/></svg>
            </button>
            <input type="text" inputmode="numeric" autocomplete="off" value="{{ $item->quantity }}"
                   class="qty-input h-9 w-12 border-x border-slate-200 text-center text-sm font-semibold text-slate-900 focus:outline-none">
            <button type="button" class="qty-inc flex h-9 w-9 items-center justify-center rounded-r-lg text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-900" aria-label="Increase quantity">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        </div>

        <button type="button" class="remove-btn hidden text-sm font-medium text-slate-400 transition-colors hover:text-red-600 sm:inline" data-name="{{ $item->product->name }}">Remove</button>
        <button type="button" class="remove-btn flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 sm:hidden" data-name="{{ $item->product->name }}" aria-label="Remove">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
        </button>
    </div>
</div>
