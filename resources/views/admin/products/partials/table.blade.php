{{--
    Maqueta de escritorio del listado de productos (desde el breakpoint `sm:`).

    Vive separada de partials/list.blade.php sólo por tamaño: la incluye esa vista
    dentro del `@else`, así que sigue siendo un único fragmento lo que devuelve
    `Admin\ProductController@filters` y lo que reemplaza Alpine con `innerHTML`.
    Por lo mismo, sin directivas de Alpine dentro.

    Variable: $products (Collection no vacía).
--}}
<div class="hidden sm:block bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disponibilidad</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alérgenos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($products as $product)
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
                    <span class="inline-block text-xs px-2 py-1 rounded-full {{ $product->available ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                        {{ $product->available ? 'Disponible' : 'No disponible' }}
                    </span>
                </td>
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
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" data-confirm="¿Estás seguro de eliminar este producto?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
