<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use Illuminate\Http\Request;

class AlergenController extends Controller
{
    public function index()
    {
        $allergens = Allergen::withCount('products')->latest()->get();
        return view('admin.allergens.index', ['allergens' => $allergens]);
    }

    public function create()
    {
        return view('admin.allergens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allergens,name',
        ]);

        Allergen::create($validated);

        return redirect()->route('admin.allergens.index')->with('success', 'Alérgeno creado correctamente.');
    }

    public function edit(Allergen $allergen) 
    {
        return view('admin.allergens.edit', ['allergen' => $allergen]);
    }

    public function update(Request $request, Allergen $allergen)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allergens,name,' . $allergen->id,
        ]);

        $allergen->update($validated);

        return redirect()->route('admin.allergens.index')->with('success', 'Alérgeno actualizado correctamente.');
    }

    public function destroy(Allergen $allergen)
    {
        $allergen->products()->detach();
        $allergen->delete();
        return redirect()->route('admin.allergens.index')->with('success', 'Alérgeno eliminado correctamente.');
    }
}
