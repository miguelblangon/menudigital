<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Listado del panel. Los filtros (`search`, `category`) se aplican aquí mismo,
     * no sólo en el cliente: así `GET /admin/products?search=tortilla&category=3`
     * es un enlace válido que sobrevive a una recarga, al botón atrás y a un
     * JavaScript roto, y el primer render ya coincide con el estado de Alpine.
     */
    public function index(Request $request): View
    {
        return view("admin.products.index", [
            ...$this->filterState($request),
            "categories" => $this->categories(),
        ]);
    }

    /**
     * El mismo listado, sin la página alrededor: es el fragmento que pide Alpine
     * con `fetch` al escribir en el buscador o cambiar la categoría, y que la
     * vista sustituye por el contenido del contenedor. Se responde HTML y no JSON
     * a propósito: el markup de las tarjetas (móvil) y de la tabla (escritorio)
     * sigue siendo un único Blade con sus `@csrf` y su escapado, en lugar de
     * duplicarse en plantillas de Alpine del otro lado del cable.
     */
    public function filters(Request $request): View
    {
        return view("admin.products.partials.list", $this->filterState($request));
    }

    public function create(): View
    {
        return view("admin.products.create", [
            "categories" => $this->categories(),
            "allergens" => $this->allergens(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $product = new Product($this->attributes($validated, $request));

        if ($request->hasFile("image")) {
            $product->image = $request->file("image")->store("products", "public");
        }

        $product->save();
        $product->allergens()->sync($validated["allergens"] ?? []);

        return redirect()->route("admin.products.index")->with("success", "Producto creado correctamente.");
    }

    public function edit(Product $product): View
    {
        return view("admin.products.edit", [
            "product" => $product,
            "categories" => $this->categories(),
            "allergens" => $this->allergens(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $attributes = $this->attributes($validated, $request);

        if ($request->hasFile("image")) {
            if ($product->image) {
                Storage::disk("public")->delete($product->image);
            }

            $attributes["image"] = $request->file("image")->store("products", "public");
        }

        $product->update($attributes);
        $product->allergens()->sync($validated["allergens"] ?? []);

        return redirect()->route("admin.products.index")->with("success", "Producto actualizado correctamente.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image) {
            Storage::disk("public")->delete($product->image);
        }

        $product->allergens()->detach();
        $product->delete();

        return redirect()->route("admin.products.index")->with("success", "Producto eliminado correctamente.");
    }

    /**
     * Estado compartido por el primer render (`index`) y por el refresco async
     * (`filters`). Se calcula en un único punto para que ambos devuelvan
     * exactamente lo mismo ante la misma query: si se bifurcan, el listado que
     * pinta Alpine al primer tecleo es distinto del que pintó Blade al cargar.
     *
     * @return array{products: Collection<int, Product>, search: ?string, categoryId: ?int, filtersActive: bool}
     */
    private function filterState(Request $request): array
    {
        $search = $this->searchTerm($request);
        $categoryId = $this->categoryId($request);

        return [
            "products" => $this->filteredProducts($search, $categoryId),
            "search" => $search,
            "categoryId" => $categoryId,
            "filtersActive" => $search !== null || $categoryId !== null,
        ];
    }

    /**
     * Consulta del listado: mismos `with()` y mismo orden (`latest()`) en el
     * primer render y en el refresco async, para que al filtrar no cambie ni el
     * orden de las filas.
     *
     * @return Collection<int, Product>
     */
    private function filteredProducts(?string $search, ?int $categoryId): Collection
    {
        return Product::query()
            ->with(["category", "allergens"])
            ->when($categoryId !== null, fn (Builder $query) => $query->where("category_id", $categoryId))
            // LIKE sin escapar `%` ni `_`: en SQLite no hay carácter de escape por
            // defecto (haría falta un `ESCAPE '\'` explícito) y añadir la barra le
            // devolvería al usuario un término que no coincida consigo mismo.
            // SQLite resuelve LIKE sin distinguir mayúsculas en ASCII, así que
            // "croq" encuentra "Croquetas"; los acentos sí son literales ("cafe"
            // no encuentra "Café"), del mismo modo que en la carta sembrada.
            ->when($search !== null, fn (Builder $query) => $query->where("name", "like", "%{$search}%"))
            ->latest()
            ->get();
    }

    /**
     * Término de búsqueda normalizado: vacío significa "sin filtro", y se corta a
     * 100 caracteres para que nadie pegue un párrafo entero en el buscador.
     */
    private function searchTerm(Request $request): ?string
    {
        $search = trim((string) $request->input("search", ""));

        return $search === "" ? null : mb_substr($search, 0, 100);
    }

    /**
     * Id de categoría de la query, o null si no existe tal categoría. Un id
     * basura (la categoría se borró con la pestaña abierta, un enlace tocado a
     * mano) debe dejar el listado completo: filtrar por algo inexistente no es
     * un error del usuario, es un filtro que no aplica a nada.
     */
    private function categoryId(Request $request): ?int
    {
        $categoryId = (int) $request->input("category", 0);

        if ($categoryId <= 0) {
            return null;
        }

        return Category::query()->whereKey($categoryId)->exists() ? $categoryId : null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            "category_id" => ["required", "integer", "exists:categories,id"],
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string", "max:5000"],
            "price" => ["required", "numeric", "min:0", "max:999999.99"],
            "image" => ["nullable", "image", "mimes:jpg,jpeg,png,webp", "max:2048"],
            "available" => ["nullable", "boolean"],
            "allergens" => ["array"],
            // Sin exists:*, un id inexistente reventaba con un error 500 en el sync().
            "allergens.*" => ["integer", "distinct", "exists:allergens,id"],
        ];
    }

    /**
     * `allergens` e `image` no son atributos de mas assignment: el primero se
     * sincroniza con el pivot y la segunda se sube al disco.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated, Request $request): array
    {
        $attributes = Arr::except($validated, ["allergens", "image"]);

        // El formulario manda siempre el campo (hidden 0 + checkbox 1); si no viene
        // se respeta el valor por defecto de la BD para no apagar productos "sin querer".
        if ($request->has("available")) {
            $attributes["available"] = $request->boolean("available");
        }

        return $attributes;
    }

    /**
     * @return Collection<int, Category>
     */
    private function categories()
    {
        return Category::query()->orderBy("name")->get();
    }

    /**
     * @return Collection<int, Allergen>
     */
    private function allergens()
    {
        return Allergen::query()->orderBy("name")->get();
    }
}
