<?php

namespace App\Http\Controllers;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Carta pública. Los filtros también se aplican en el servidor para que los
     * enlaces profundos (el QR o un enlace compartido con ?category=...&allergens[]=...)
     * funcionen sin JS y el estado inicial del cliente sea coherente.
     */
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->orderBy("order")
            ->orderBy("name")
            ->get(["id", "name", "slug"]);

        $allergens = Allergen::query()
            ->orderBy("name")
            ->get(["id", "name"]);

        $activeCategory = null;

        if ($request->filled("category")) {
            $activeCategory = $categories->firstWhere("slug", (string) $request->input("category"));

            if ($activeCategory === null) {
                abort(404);
            }
        }

        $selectedAllergens = $this->selectedAllergenIds($request);
        $maxPrice = $this->maxPriceFilter($request);

        $products = Product::query()
            ->with(["category:id,name,slug", "allergens:id,name"])
            ->available()
            ->when($activeCategory, fn ($query) => $query->where("category_id", $activeCategory->id))
            ->when($selectedAllergens !== [], function ($query) use ($selectedAllergens) {
                // whereDoesntHave (no doesntHave): el alias correcto para excluir
                // productos que tengan ALGUNO de los alérgenos seleccionados.
                $query->whereDoesntHave("allergens", function ($subQuery) use ($selectedAllergens) {
                    $subQuery->whereIn("allergens.id", $selectedAllergens);
                });
            })
            ->when($maxPrice !== null, fn ($query) => $query->where("price", "<=", $maxPrice))
            // Primero el orden de la categoría (subconsulta: no hace falta unir tablas
            // y así se respeta el orden manual del admin) y después el nombre.
            ->orderBy(
                Category::query()
                    ->select("order")
                    ->whereColumn("categories.id", "products.category_id")
            )
            ->orderBy("name")
            ->get();

        $selectedAllergenValues = $this->selectedAllergenValues($allergens, $selectedAllergens);

        // Estado inicial de Alpine. Se compone en PHP y la vista sólo hace
        // @json($menuState): Blade compila la directiva partiendo el argumento
        // con explode(",") (Illuminate\View\Compilers\Concerns\CompilesJson), así
        // que un array literal con varias claves o con closures dentro deja la
        // expresión truncada y la vista no compila. Una sola variable esquiva el
        // límite y deja claro qué viaja al cliente.
        $menuState = [
            "categories" => $categories,
            "products" => $products,
            "activeCategory" => $activeCategory?->slug,
            "selectedAllergenValues" => $selectedAllergenValues,
            "allergenOptions" => $allergens
                ->map(fn (Allergen $allergen) => [
                    "value" => mb_strtolower($allergen->name),
                    "label" => $allergen->name,
                ])
                ->values()
                ->all(),
        ];

        return view("menu", [
            "categories" => $categories,
            "allergens" => $allergens,
            "products" => $products,
            "activeCategory" => $activeCategory,
            "selectedAllergens" => $selectedAllergens,
            "selectedAllergenValues" => $selectedAllergenValues,
            "maxPrice" => $maxPrice,
            "menuState" => $menuState,
        ]);
    }

    /**
     * ids de alérgenos de la query, dejándose solo los que existen en BD: un
     * valor basura no debe alterar la consulta.
     *
     * @return list<int>
     */
    private function selectedAllergenIds(Request $request): array
    {
        $candidates = array_map("intval", (array) $request->input("allergens", []));
        $candidates = array_values(array_unique(array_filter($candidates, fn (int $id) => $id > 0)));

        if ($candidates === []) {
            return [];
        }

        return array_map("intval", Allergen::query()->whereKey($candidates)->pluck("id")->all());
    }

    private function maxPriceFilter(Request $request): ?float
    {
        $value = $request->input("max_price");

        if (! is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Nombres en minúscula de los alérgenos filtrados: el filtro cliente (Alpine)
     * trabaja con nombres, el servidor con ids. Se hidratan los dos para que el
     * estado inicial coincida con los productos ya devueltos.
     *
     * @param  Collection<int, Allergen>  $allergens
     * @param  list<int>  $selectedAllergens
     * @return list<string>
     */
    private function selectedAllergenValues($allergens, array $selectedAllergens): array
    {
        return $allergens
            ->filter(fn (Allergen $allergen) => in_array((int) $allergen->id, $selectedAllergens, true))
            ->map(fn (Allergen $allergen) => mb_strtolower($allergen->name))
            ->values()
            ->all();
    }
}
