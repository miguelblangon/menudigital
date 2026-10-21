<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Allergen;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with("products")->get();
        $allergens = Allergen::all();
        
        // Construir consulta base
        $query = Product::with(["category", "allergens"]);
        
        // Filtrar por categoría si se especifica
        if ($request->has("category")) {
            $slug = $request->input("category");
            $activeCategory = $categories->firstWhere("slug", $slug);
            if ($activeCategory) {
                $query->where("category_id", $activeCategory->id);
            }
        } else {
            $activeCategory = null;
        }
        
        // Filtrar por alérgenos (excluir productos que tengan estos alérgenos)
        if ($request->has("allergens") && is_array($request->input("allergens"))) {
            $allergenIds = $request->input("allergens");
            $query->doesntHave("allergens", function ($q) use ($allergenIds) {
                $q->whereIn("allergens.id", $allergenIds);
            });
        }
        
        // Filtrar por precio máximo
        if ($request->has("max_price") && $request->input("max_price") > 0) {
            $query->where("price", "<=", $request->input("max_price"));
        }
        
        $products = $query->get();

        return view("menu", [
            "categories" => $categories,
            "products" => $products,
            "activeCategory" => $activeCategory,
            "allergens" => $allergens,
            "selectedAllergens" => $request->input("allergens", []),
            "maxPrice" => $request->input("max_price"),
        ]);
    }
}