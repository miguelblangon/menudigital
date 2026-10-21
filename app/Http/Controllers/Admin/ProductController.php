<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Allergen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(["category", "allergens"])->latest()->get();
        return view("admin.products.index", ["products" => $products]);
    }

    public function create()
    {
        $categories = Category::all();
        $allergens = Allergen::all();
        return view("admin.products.create", ["categories" => $categories, "allergens" => $allergens]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "category_id" => "required|exists:categories,id",
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "price" => "required|numeric|min:0",
            "image" => "nullable|image|max:2048",
            "allergens" => "nullable|array",
            "available" => "boolean",
        ]);

        $imagePath = null;
        if ($request->hasFile("image")) {
            $imagePath = $request->file("image")->store("products", "public");
        }

        $product = Product::create([
            "category_id" => $validated["category_id"],
            "name" => $validated["name"],
            "description" => $validated["description"] ?? null,
            "price" => $validated["price"],
            "image" => $imagePath,
            "available" => $validated["available"] ?? true,
        ]);

        if ($request->has("allergens")) {
            $product->allergens()->sync($validated["allergens"]);
        }

        return redirect()->route("admin.products.index")->with("success", "Producto creado correctamente.");
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $allergens = Allergen::all();
        return view("admin.products.edit", ["product" => $product, "categories" => $categories, "allergens" => $allergens]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            "category_id" => "required|exists:categories,id",
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "price" => "required|numeric|min:0",
            "image" => "nullable|image|max:2048",
            "allergens" => "nullable|array",
            "available" => "boolean",
        ]);

        if ($request->hasFile("image")) {
            if ($product->image) {
                Storage::disk("public")->delete($product->image);
            }
            $validated["image"] = $request->file("image")->store("products", "public");
        } else {
            unset($validated["image"]);
        }

        $product->update($validated);

        if ($request->has("allergens")) {
            $product->allergens()->sync($validated["allergens"]);
        }

        return redirect()->route("admin.products.index")->with("success", "Producto actualizado correctamente.");
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk("public")->delete($product->image);
        }
        $product->allergens()->detach();
        $product->delete();

        return redirect()->route("admin.products.index")->with("success", "Producto eliminado correctamente.");
    }
}