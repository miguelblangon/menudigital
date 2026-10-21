<?php

namespace Tests\Feature;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    private function assertVisibleProducts(int $status, string $uri, array $expected): void
    {
        $this->get($uri)
            ->assertStatus($status)
            ->assertViewHas("products", function ($products) use ($expected) {
                return $products->pluck("name")->all() === $expected;
            });
    }

    public function test_hides_products_that_are_not_available(): void
    {
        $category = Category::create(["name" => "Entrantes", "slug" => "entrantes"]);
        Product::create(["category_id" => $category->id, "name" => "Aceitunas", "price" => 3.0, "available" => true]);
        Product::create(["category_id" => $category->id, "name" => "Agotado", "price" => 4.0, "available" => false]);

        $this->assertVisibleProducts(200, "/", ["Aceitunas"]);
    }

    public function test_filters_by_category_slug(): void
    {
        $entrantes = Category::create(["name" => "Entrantes", "slug" => "entrantes", "order" => 1]);
        $postres = Category::create(["name" => "Postres", "slug" => "postres", "order" => 2]);

        Product::create(["category_id" => $entrantes->id, "name" => "Aceitunas", "price" => 3.0]);
        Product::create(["category_id" => $postres->id, "name" => "Flan", "price" => 5.0]);

        $this->assertVisibleProducts(200, "/?category=postres", ["Flan"]);
    }

    public function test_excludes_products_that_contain_a_selected_allergen(): void
    {
        $category = Category::create(["name" => "Panadería", "slug" => "panaderia"]);
        $gluten = Allergen::create(["name" => "Gluten"]);

        $pan = Product::create(["category_id" => $category->id, "name" => "Pan", "price" => 1.0]);
        $agua = Product::create(["category_id" => $category->id, "name" => "Agua", "price" => 1.5]);

        $pan->allergens()->attach($gluten);

        $this->assertVisibleProducts(200, "/?allergens[]=".$gluten->id, ["Agua"]);
    }

    public function test_ignores_allergen_ids_that_do_not_exist(): void
    {
        $category = Category::create(["name" => "Bebidas", "slug" => "bebidas"]);
        Product::create(["category_id" => $category->id, "name" => "Agua", "price" => 1.5]);

        // Antes el filtro no validaba y el sync/where sobre ids inexistentes reventaba.
        $this->assertVisibleProducts(200, "/?allergens[]=999", ["Agua"]);
    }

    public function test_filters_by_max_price(): void
    {
        $category = Category::create(["name" => "Vinos", "slug" => "vinos"]);
        Product::create(["category_id" => $category->id, "name" => "Tinto de la casa", "price" => 12.0]);
        Product::create(["category_id" => $category->id, "name" => "Reserva", "price" => 35.0]);

        $this->assertVisibleProducts(200, "/?max_price=20", ["Tinto de la casa"]);
    }

    public function test_orders_products_by_category_order_and_name(): void
    {
        $entrantes = Category::create(["name" => "Entrantes", "slug" => "entrantes", "order" => 1]);
        $postres = Category::create(["name" => "Postres", "slug" => "postres", "order" => 2]);

        Product::create(["category_id" => $postres->id, "name" => "Flan", "price" => 5.0]);
        Product::create(["category_id" => $entrantes->id, "name" => "Zumo", "price" => 2.0]);
        Product::create(["category_id" => $entrantes->id, "name" => "Aceitunas", "price" => 3.0]);

        $this->assertVisibleProducts(200, "/", ["Aceitunas", "Zumo", "Flan"]);
    }

    public function test_unknown_category_slug_returns_404(): void
    {
        $this->get("/?category=inexistente")->assertNotFound();
    }

    public function test_menu_is_public(): void
    {
        $this->get("/")->assertOk();
    }

    public function test_alpine_state_travels_as_parsable_json_and_cannot_break_out(): void
    {
        $category = Category::create(["name" => "Panaderia", "slug" => "panaderia"]);
        $gluten = Allergen::create(["name" => "Gluten"]);

        // Nombre hostil: si @json dejara de escapar <, > o ", el payload cerraría
        // el bloque application/json y pasaría a ser JavaScript ejecutable.
        $bread = Product::create([
            "category_id" => $category->id,
            "name" => "Barra </script><script>alert(1)</script>",
            "description" => 'Con "comillas" y &符号',
            "price" => 1.0,
            "available" => true,
        ]);
        $bread->allergens()->attach($gluten);

        $html = $this->get("/?category=panaderia")->assertOk()->getContent();

        preg_match('~id="menu-data">\s*(.*?)\s*</script>~s', $html, $matches);

        // Blade parte el argumento de @json con explode(",") (ver
        // Illuminate\View\Compilers\Concerns\CompilesJson) y tira tramos enteros:
        // alguna vez el bloque quedó truncado y esto no decodificaba.
        $this->assertNotEmpty($matches[1] ?? null, "No se ha encontrado el bloque #menu-data.");

        $state = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            ["categories", "products", "activeCategory", "selectedAllergenValues", "allergenOptions"],
            array_keys($state)
        );
        $this->assertSame("panaderia", $state["activeCategory"]);
        $this->assertSame([], $state["selectedAllergenValues"]);
        $this->assertSame(["gluten"], array_column($state["allergenOptions"], "value"));
        $this->assertSame("Gluten", $state["allergenOptions"][0]["label"]);
        $this->assertSame(
            "Barra </script><script>alert(1)</script>",
            $state["products"][0]["name"],
            "El estado debe conservar el nombre exacto, ya decodificado por el navegador."
        );

        // ...pero nunca en crudo dentro del HTML: @json lo manda como entidades.
        $this->assertStringNotContainsString("</script>", $matches[1]);
        $this->assertStringNotContainsString("<script>", $matches[1]);
    }
}
