<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllergenController extends Controller
{
    public function index(): View
    {
        $allergens = Allergen::query()
            ->withCount("products")
            ->orderBy("name")
            ->get();

        return view("admin.allergens.index", ["allergens" => $allergens]);
    }

    public function create(): View
    {
        return view("admin.allergens.create");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", "unique:allergens,name"],
        ]);

        Allergen::create(["name" => $validated["name"]]);

        return redirect()->route("admin.allergens.index")->with("success", "Alérgeno creado correctamente.");
    }

    public function edit(Allergen $allergen): View
    {
        return view("admin.allergens.edit", ["allergen" => $allergen]);
    }

    public function update(Request $request, Allergen $allergen): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", "unique:allergens,name,".$allergen->id],
        ]);

        $allergen->update(["name" => $validated["name"]]);

        return redirect()->route("admin.allergens.index")->with("success", "Alérgeno actualizado correctamente.");
    }

    public function destroy(Allergen $allergen): RedirectResponse
    {
        $allergen->products()->detach();
        $allergen->delete();

        return redirect()->route("admin.allergens.index")->with("success", "Alérgeno eliminado correctamente.");
    }
}
