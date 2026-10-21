<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with(["category", "allergens"])->latest()->get();

        return view("admin.products.index", ["products" => $products]);
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
