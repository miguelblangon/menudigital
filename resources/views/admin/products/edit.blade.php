@extends('layouts.admin')

@section('title', 'Editar Producto')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">Editar Producto</h1>

<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
        <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $product->description) }}</textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Precio (€)</label>
            <input type="number" name="price" step="0.01" value="{{ old('price', $product->price) }}" required class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
            <select name="category_id" required class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Imagen</label>
        @if($product->image)
            <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded-lg mb-2">
        @endif
        <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilidad</label>
        <input type="hidden" name="available" value="0">
        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors w-fit">
            <input type="checkbox" name="available" value="1" {{ old('available', $product->available) ? 'checked' : '' }} class="rounded h-4 w-4">
            <span class="text-sm">Disponible en la carta</span>
        </label>
        <p class="text-xs text-gray-500 mt-1">Si se desmarca, el producto no aparece en la carta pública.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Alérgenos</label>
        @if(count($allergens) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($allergens as $allergen)
                    <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="checkbox" name="allergens[]" value="{{ $allergen->id }}" {{ in_array($allergen->id, old('allergens', $product->allergens->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded h-4 w-4">
                        <span class="text-sm">{{ $allergen->name }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">No hay alérgenos definidos. <a href="{{ route('admin.allergens.create') }}" class="text-blue-600 hover:underline">Crear uno</a></p>
        @endif
    </div>

    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium transition-colors w-full sm:w-auto">
            Actualizar Producto
        </button>
        <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 font-medium transition-colors w-full sm:w-auto text-center">
            Cancelar
        </a>
    </div>
</form>
@endsection