<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount("products")->latest()->get();
        return view("admin.categories.index", ["categories" => $categories]);
    }

    public function create()
    {
        return view("admin.categories.create");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255|unique:categories,name",
        ]);

        Category::create([
            "name" => $validated["name"],
            "slug" => Str::slug($validated["name"]),
        ]);

        return redirect()->route("admin.categories.index")->with("success", "Categoría creada correctamente.");
    }

    public function edit(Category $category)
    {
        return view("admin.categories.edit", ["category" => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255|unique:categories,name," . $category->id,
        ]);

        $category->update([
            "name" => $validated["name"],
            "slug" => Str::slug($validated["name"]),
        ]);

        return redirect()->route("admin.categories.index")->with("success", "Categoría actualizada correctamente.");
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route("admin.categories.index")->with("success", "Categoría eliminada correctamente.");
    }
}