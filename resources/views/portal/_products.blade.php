@foreach($products as $product)
    <article class="product-card flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="aspect-square w-full bg-slate-100">
            <img src="{{ $product->image_url ?: asset('img/product-placeholder.svg') }}"
                 alt="{{ $product->name }}" loading="lazy" decoding="async"
                 onload="this.classList.remove('opacity-0')"
                 onerror="this.onerror=null;this.src='{{ asset('img/product-placeholder.svg') }}';"
                 class="h-full w-full object-cover opacity-0 transition-opacity duration-500">
        </div>

        <div class="flex flex-1 flex-col p-4">
            <h3 class="flex-1 text-sm font-semibold text-slate-900">{{ $product->name }}</h3>

            <div class="mt-4">
                {{-- Quantity stepper --}}
                <div class="flex w-full items-center rounded-lg border border-slate-200">
                    <button type="button" data-step="-1" aria-label="Decrease quantity"
                            class="qty-step flex h-9 w-9 shrink-0 items-center justify-center text-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14"/></svg>
                    </button>
                    <input type="text" value="1" inputmode="numeric" autocomplete="off"
                           class="qty-input h-9 w-full min-w-0 flex-1 border-x border-slate-200 text-center text-sm font-medium text-slate-900 focus:outline-none">
                    <button type="button" data-step="1" aria-label="Increase quantity"
                            class="qty-step flex h-9 w-9 shrink-0 items-center justify-center text-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>
            </div>

            <button type="button"
                    class="add-to-cart mt-3 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800 active:bg-slate-950"
                    data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                Add to Cart
            </button>
        </div>
    </article>
@endforeach
