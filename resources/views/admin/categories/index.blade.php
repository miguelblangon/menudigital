@extends('layouts.admin')

@section('title', 'Categorías')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Categorías</h1>
    <a href="{{ route('admin.categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto text-center">
        + Nueva Categoría
    </a>
</div>

<!-- Mobile: Card Layout -->
<div class="space-y-3 sm:hidden">
    @forelse($categories as $category)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-gray-900">{{ $category->name }}</h3>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $category->products_count }} productos</span>
        </div>
        <p class="text-sm text-gray-500 mb-3">/{{ $category->slug }}</p>
        <div class="flex gap-2">
            <a href="{{ route('admin.categories.edit', $category) }}" class="flex-1 bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium text-center hover:bg-blue-100 transition-colors">
                Editar
            </a>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="flex-1" data-confirm="¿Eliminar esta categoría?">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No hay categorías creadas aún.</p>
    </div>
    @endforelse
</div>

<!-- Desktop: Table Layout -->
<div class="hidden sm:block bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Productos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($categories as $category)
            <tr>
                <td class="px-6 py-4 font-medium text-gray-900">{{ $category->name }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $category->slug }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $category->products_count }}</td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800 mr-3">Editar</a>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" data-confirm="¿Estás seguro de eliminar esta categoría?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay categorías creadas aún.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection