@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto text-center">
        + Nuevo Producto
    </a>
</div>

<div
    x-data="productFilters()"
    data-product-filters
    data-endpoint="{{ route('admin.products.filters') }}"
    data-list-url="{{ route('admin.products.index') }}"
>
    {{--
        Es un <form method="GET"> de libro, no un par de inputs sueltos: con
        JavaScript desactivado sigue filtrando (el navegador recarga con la query),
        y con JavaScript activo Alpine intercepta el submit y lo convierte en un
        fetch sin recarga. Por eso los valores viven en el DOM y no en una copia
        del estado: hay una sola fuente de verdad y el `name` del campo ES el
        parámetro de la query.
    --}}
    <form
        method="GET"
        action="{{ route('admin.products.index') }}"
        data-filter-form
        @submit.prevent="apply()"
        aria-label="Filtrar productos"
        class="bg-white rounded-lg shadow p-4 mb-4 flex flex-col sm:flex-row sm:items-end gap-3"
    >
        <div class="flex-1 min-w-0">
            <label for="product-search" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                Nombre
            </label>
            <input
                type="search"
                id="product-search"
                name="search"
                value="{{ $search }}"
                placeholder="Buscar por nombre…"
                autocomplete="off"
                data-filter-field
                @input.debounce.300ms="apply()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
        </div>

        <div class="sm:w-56">
            <label for="product-category" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                Categoría
            </label>
            <select
                id="product-category"
                name="category"
                data-filter-field
                @change="apply()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === (int) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            {{-- Enlace, no botón: sin JavaScript lleva al listado sin filtros.
                 Con JavaScript activo Alpine lo intercepta y limpia sin recargar. --}}
            <a
                href="{{ route('admin.products.index') }}"
                @click.prevent="clear()"
                x-show="hasFilters"
                class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
            >
                Limpiar
            </a>

            {{-- Un único botón, siempre visible: sin JavaScript hace el submit de
                 siempre (recarga con la query) y con JavaScript activo el
                 `@submit.prevent` de la etiqueta <form> lo convierte en fetch.
                 No lleva `x-cloak`: Alpine quita ese atributo al inicializar, así
                 que ocultarlo así lo dejaría visible justo cuando JS SÍ funciona. --}}
            <button
                type="submit"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors"
            >
                Buscar
            </button>
        </div>
    </form>

    <p role="status" x-cloak x-show="loading" class="text-sm text-gray-500 mb-3">Actualizando el listado…</p>
    <p role="alert" x-cloak x-show="error" x-text="error" class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"></p>

    {{-- El único nodo que se reemplaza. Dentro no hay directivas de Alpine: el
         partial se inyecta con innerHTML y Alpine no recorre los nodos nuevos. --}}
    <div data-product-list>
        @include('admin.products.partials.list')
    </div>
</div>
@endsection
