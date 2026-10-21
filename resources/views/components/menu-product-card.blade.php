<div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-stone-100 hover:shadow-md transition-shadow">
    <!-- Product Image -->
    <div class="relative h-48 overflow-hidden">
        {{-- La imagen siempre es local (/storage): img-src 'self' la permite. Las URLs
             remotas ya no se admiten, ni como dependencia externa ni abriendo la CSP. --}}
        <img
            :src="product.image ? '/storage/' + product.image : '/images/no-image.svg'"
            :alt="product.name"
            class="w-full h-full object-cover"
            loading="lazy"
        >
    </div>

    <!-- Product Info -->
    <div class="p-4">
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-lg font-semibold text-stone-800 font-display" x-text="product.name"></h3>
            <span class="text-lg font-bold text-amber-700 whitespace-nowrap" x-text="'€' + product.price.toFixed(2)"></span>
        </div>
        
        <p class="mt-1 text-sm text-stone-600 line-clamp-2" x-text="product.description"></p>

        <!-- Allergens -->
        <div class="mt-3 flex flex-wrap gap-1">
            <template x-for="allergen in product.allergens" :key="allergen.id">
                <span class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded-full text-xs font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span x-text="allergen.name"></span>
                </span>
            </template>
        </div>
    </div>
</div>