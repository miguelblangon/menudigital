<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount("products")
            ->orderBy("order")
            ->orderBy("name")
            ->get();

        return view("admin.categories.index", ["categories" => $categories]);
    }

    public function create(): View
    {
        return view("admin.categories.create");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", "unique:categories,name"],
        ]);

        Category::create([
            "name" => $validated["name"],
            "slug" => $this->uniqueSlug($validated["name"]),
        ]);

        return redirect()->route("admin.categories.index")->with("success", "Categoría creada correctamente.");
    }

    public function edit(Category $category): View
    {
        return view("admin.categories.edit", ["category" => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", "unique:categories,name,".$category->id],
        ]);

        $category->update([
            "name" => $validated["name"],
            "slug" => $this->uniqueSlug($validated["name"], $category->id),
        ]);

        return redirect()->route("admin.categories.index")->with("success", "Categoría actualizada correctamente.");
    }

    /**
     * La FK de products está definida `on delete cascade`: borrar una categoría
     * con productos se llevaría esos productos por delante (y dejaría huérfanos
     * los archivos de imagen en `storage/app/public/products`, porque el borrado
     * de ficheros solo ocurre en ProductController::destroy).
     * Se bloquea a nivel de aplicación y se explica al usuario qué hacer.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $products = $category->products()->count();

        if ($products > 0) {
            return redirect()
                ->route("admin.categories.index")
                ->withErrors([
                    "category" => "«{$category->name}» tiene {$products} producto(s) asignados. Reasígnalos o elimínalos antes de borrar la categoría.",
                ]);
        }

        $category->delete();

        return redirect()->route("admin.categories.index")->with("success", "Categoría eliminada correctamente.");
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: "categoria";
        $slug = $base;
        $suffix = 2;

        while (
            Category::query()
                ->where("slug", $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
