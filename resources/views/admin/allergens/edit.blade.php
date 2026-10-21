@extends('layouts.admin')

@section('title', 'Editar Alérgeno')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">Editar Alérgeno</h1>

<form action="{{ route('admin.allergens.update', $allergen) }}" method="POST" class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $allergen->name) }}" required class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>

    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium transition-colors w-full sm:w-auto">
            Actualizar Alérgeno
        </button>
        <a href="{{ route('admin.allergens.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 font-medium transition-colors w-full sm:w-auto text-center">
            Cancelar
        </a>
    </div>
</form>
@endsection