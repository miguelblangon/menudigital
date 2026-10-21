@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto text-center">
        + Nuevo Producto
    </a>
</div>

<!-- Mobile: Card Layout -->
<div class="space-y-3 sm:hidden">
    @forelse($products as $product)
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
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors" onclick="return confirm('¿Eliminar este producto?')">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No hay productos creados aún.</p>
    </div>
    @endforelse
</div>

<!-- Desktop: Table Layout -->
<div class="hidden sm:block bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alérgenos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($products as $product)
            <tr>
                <td class="px-6 py-4">
                    @if($product->image)
                        <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded">
                    @else
                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400">?</div>
                    @endif
                </td>
                <td class="px-6 py-4 font-medium text-gray-900">{{ $product->name }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $product->category->name }}</td>
                <td class="px-6 py-4 text-gray-900">€{{ number_format($product->price, 2) }}</td>
                <td class="px-6 py-4">
                    @if(count($product->allergens) > 0)
                        @foreach($product->allergens as $allergen)
                            <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs mr-1">{{ $allergen->name }}</span>
                        @endforeach
                    @else
                        <span class="text-gray-400 text-sm">Sin alérgenos</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 mr-3">Editar</a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay productos creados aún.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection