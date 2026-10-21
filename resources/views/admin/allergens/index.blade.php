@extends('layouts.admin')

@section('title', 'Alérgenos')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Alérgenos</h1>
    <a href="{{ route('admin.allergens.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto text-center">
        + Nuevo Alérgeno
    </a>
</div>

<!-- Mobile: Card Layout -->
<div class="space-y-3 sm:hidden">
    @forelse($allergens as $alergen)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-gray-900">{{ $alergen->name }}</h3>
            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">{{ $alergen->products_count }} productos</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.allergens.edit', $alergen) }}" class="flex-1 bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium text-center hover:bg-blue-100 transition-colors">
                Editar
            </a>
            <form action="{{ route('admin.allergens.destroy', $alergen) }}" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors" onclick="return confirm('¿Eliminar este alérgeno?')">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No hay alérgenos creados aún.</p>
    </div>
    @endforelse
</div>

<!-- Desktop: Table Layout -->
<div class="hidden sm:block bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Productos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($allergens as $alergen)
            <tr>
                <td class="px-6 py-4 font-medium text-gray-900">{{ $alergen->name }}</td>
                <td class="px-6 py-4">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">{{ $alergen->products_count }} productos</span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.allergens.edit', $alergen) }}" class="text-blue-600 hover:text-blue-800 mr-3">Editar</a>
                    <form action="{{ route('admin.allergens.destroy', $alergen) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar este alérgeno?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay alérgenos creados aún.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection