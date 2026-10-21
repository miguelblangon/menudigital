<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cafetería - Carta Digital</title>
    {{-- Alpine y las tipografías viajan en el bundle de Vite: mismo origen, sin CDNs --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 min-h-screen font-body" x-data="menuApp()">
    @include('components.menu-header')
    @include('components.menu-categories')

    @php
        // Enlaces sin JS: mantienen los demás filtros de la query.
        $categoryUrl = fn (?string $slug) => $slug === null
            ? request()->fullUrlWithoutQuery('category')
            : request()->fullUrlWithQuery(['category' => $slug]);

        $allergenUrl = function (int $id): string {
            $selected = request()->input('allergens', []);
            $selected = array_values(array_filter(array_map('intval', (array) $selected)));

            $selected = in_array($id, $selected, true)
                ? array_values(array_diff($selected, [$id]))
                : [...$selected, $id];

            return $selected === []
                ? request()->fullUrlWithoutQuery('allergens')
                : request()->fullUrlWithQuery(['allergens' => $selected]);
        };
    @endphp

    <noscript>
        <div class="max-w-lg mx-auto px-4 pt-4 space-y-3">
            <div class="flex flex-wrap gap-2">
                <a href="{{ $categoryUrl(null) }}" class="px-4 py-2 rounded-full text-sm font-medium {{ $activeCategory === null ? 'bg-stone-800 text-white' : 'bg-white text-stone-600 border border-stone-200' }}">Todos</a>
                @foreach($categories as $category)
                    <a href="{{ $categoryUrl($category->slug) }}" class="px-4 py-2 rounded-full text-sm font-medium {{ $activeCategory?->slug === $category->slug ? 'bg-stone-800 text-white' : 'bg-white text-stone-600 border border-stone-200' }}">{{ $category->name }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($allergens as $allergen)
                    <a href="{{ $allergenUrl((int) $allergen->id) }}" class="px-3 py-1.5 rounded-full text-xs font-medium border {{ in_array((int) $allergen->id, $selectedAllergens, true) ? 'bg-red-100 text-red-800 border-red-300' : 'bg-white text-stone-600 border-stone-200' }}">Sin {{ $allergen->name }}</a>
                @endforeach
            </div>
        </div>
    </noscript>

    <!-- Products Grid -->
    <main class="max-w-lg mx-auto px-4 py-6">
        <div class="grid gap-4">
            <template x-for="product in filteredProducts" :key="product.id">
                @include('components.menu-product-card')
            </template>

            <!-- Estado sin JavaScript: la carta se puede leer igualmente -->
            <noscript>
                @forelse($products as $product)
                    <div class="bg-white rounded-2xl shadow-sm border border-stone-100 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-lg font-semibold text-stone-800 font-display">{{ $product->name }}</h3>
                            <span class="text-lg font-bold text-amber-700 whitespace-nowrap">€{{ number_format($product->price, 2) }}</span>
                        </div>
                        @if($product->description)
                            <p class="mt-1 text-sm text-stone-600">{{ $product->description }}</p>
                        @endif
                        @if($product->allergens->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach($product->allergens as $allergen)
                                    <span class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded-full text-xs font-medium">{{ $allergen->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-center py-12 text-stone-500">Todavía no hay productos en la carta.</p>
                @endforelse
            </noscript>

            <!-- Empty State -->
            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-stone-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-stone-500">No hay productos que coincidan con los filtros</p>
                </div>
            </template>
        </div>
    </main>

    @include('components.menu-allergen-filter')

    {{--
        Estado inicial de Alpine. Viaja como JSON, no como JavaScript: un bloque
        <script type="application/json"> no es ejecutable, así que una CSP con
        script-src 'self' no lo bloquea y no hace falta firmar nada con nonces.
        Blade escapa <, >, & y las comillas en @json, de modo que el nombre o la
        descripción de un producto no pueden cerrar el bloque e inyectar código.

        Los datos se hidratan desde el servidor: los enlaces del QR llegan ya
        filtrados por query y el estado inicial debe coincidir con lo enviado.

        El array se construye en MenuController, no aquí: @json parte su
        argumento con explode(","), de modo que un literal con varias claves
        pierde tramos enteros (el "]" de cierre) y la vista revienta al compilar.
        Se le pasa una única variable y la directiva queda en una línea.
    --}}
    <script type="application/json" id="menu-data">
        @json($menuState)
    </script>
</body>
</html>