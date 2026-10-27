{{--
    Listado de productos del panel: TARJETAS en móvil y TABLA desde `sm:`.

    Es un partial a propósito: lo incluye admin/products/index.blade.php en el
    primer render y lo devuelve sola `Admin\ProductController@filters` cuando
    Alpine pide el refresco. El markup de las dos maquetas vive en un único
    sitio —un Blade con su escapado `{{ }}` y sus `@csrf`— en lugar de duplicarse
    en plantillas `x-for` al otro lado del cable.

    IMPORTANTE: dentro no puede haber ni una directiva de Alpine. El contenedor
    se reemplaza con `innerHTML` y Alpine no recorre los nodos recién metidos:
    un `x-for` o un `x-show` aquí quedaría inerte tras el primer refresco. Los
    formularios de borrado siguen funcionando igual porque su confirmación no es
    un atributo inline, sino el listener delegado sobre `form[data-confirm]` de
    resources/js/app.js.

    Variables: $products (Collection), $filtersActive (bool).
--}}

@php
    $total = $products->count();
@endphp

<p class="mb-3 text-sm text-gray-500">
    {{ $total === 1 ? '1 producto' : $total.' productos' }}
    @if($filtersActive)
        <span class="text-gray-400">{{ $total === 1 ? 'que coincide' : 'que coinciden' }} con los filtros</span>
    @endif
</p>

@if($total === 0)
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">
            {{ $filtersActive ? 'Ningún producto coincide con la búsqueda.' : 'No hay productos creados aún.' }}
        </p>
        @if($filtersActive)
            <p class="mt-2 text-sm text-gray-400">Prueba con otro nombre o cambia la categoría.</p>
        @endif
    </div>
@else
    {{-- Móvil: tarjetas apiladas --}}
    <div class="space-y-3 sm:hidden">
        @foreach($products as $product)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-start gap-3 mb-3">
                @if($product->image)
                    <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                @else
                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 flex-shrink-0">?</div>
                @endif
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 truncate">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $product->category->name }}</p>
                    <p class="text-lg font-bold text-gray-900">€{{ number_format($product->price, 2) }}</p>
                    <span class="mt-1 inline-block text-xs px-2 py-1 rounded-full {{ $product->available ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                        {{ $product->available ? 'Disponible' : 'No disponible' }}
                    </span>
                </div>
            </div>
            @if(count($product->allergens) > 0)
                <div class="flex flex-wrap gap-1 mb-3">
                    @foreach($product->allergens as $allergen)
                        <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">{{ $allergen->name }}</span>
                    @endforeach
                </div>
            @endif
            <div class="flex gap-2">
                <a href="{{ route('admin.products.edit', $product) }}" class="flex-1 bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium text-center hover:bg-blue-100 transition-colors">
                    Editar
                </a>
                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="flex-1" data-confirm="¿Eliminar este producto?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Escritorio: tabla (se abre en el segundo bloque de este partial) --}}
    @include('admin.products.partials.table')
@endif
