<?php

namespace Tests\Feature\Admin;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Puerta de entrada del panel.
 *
 * Los tres CRUD se protegen con un único `Route::middleware(["auth"])` que envuelve
 * los `Route::resource` de routes/web.php: si alguien mueve una resource fuera de ese
 * grupo, las páginas siguen funcionando y sólo se abre la brecha. Cada test de
 * producto/categoría/alérgeno actúa como administrador (`setUp()` con `actingAs`),
 * así que ninguno de ellos se entera de eso. Aquí no hay sesión: se verifica la
 * redirección en las siete acciones, incluidas las que escriben.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_product_crud(): void
    {
        $category = Category::create(["name" => "Entradas", "slug" => "entradas"]);
        $product = Product::create([
            "category_id" => $category->id,
            "name" => "Croquetas",
            "price" => 8.5,
            "available" => true,
        ]);

        $this->get("/admin/products")->assertRedirect("/login");
        $this->get("/admin/products/create")->assertRedirect("/login");
        $this->get("/admin/products/{$product->id}/edit")->assertRedirect("/login");

        // El fragmento HTML que consulta Alpine al filtrar también está tras
        // `auth`: es una ruta suelta dentro del grupo, no la resource.
        $this->get("/admin/products/filters?search=Croq")->assertRedirect("/login");

        $this->post("/admin/products", ["name" => "Inyectado", "price" => 1, "category_id" => $category->id])
            ->assertRedirect("/login");
        $this->put("/admin/products/{$product->id}", ["name" => "Renombrado sin sesión"])
            ->assertRedirect("/login");
        $this->delete("/admin/products/{$product->id}")->assertRedirect("/login");

        // Ni el intento de escritura ni el de borrado llegaron a la base de datos.
        $this->assertDatabaseMissing("products", ["name" => "Inyectado"]);
        $this->assertDatabaseHas("products", ["id" => $product->id, "name" => "Croquetas"]);
    }

    public function test_guests_cannot_reach_the_category_crud(): void
    {
        $category = Category::create(["name" => "Entradas", "slug" => "entradas"]);

        $this->get("/admin/categories")->assertRedirect("/login");
        $this->get("/admin/categories/create")->assertRedirect("/login");
        $this->get("/admin/categories/{$category->id}/edit")->assertRedirect("/login");

        $this->post("/admin/categories", ["name" => "Categoría inyectada"])->assertRedirect("/login");
        $this->put("/admin/categories/{$category->id}", ["name" => "Renombrada sin sesión"])
            ->assertRedirect("/login");
        $this->delete("/admin/categories/{$category->id}")->assertRedirect("/login");

        $this->assertDatabaseMissing("categories", ["name" => "Categoría inyectada"]);
        $this->assertDatabaseHas("categories", ["id" => $category->id, "name" => "Entradas"]);
    }

    public function test_guests_cannot_reach_the_allergen_crud(): void
    {
        $allergen = Allergen::create(["name" => "Gluten"]);

        $this->get("/admin/allergens")->assertRedirect("/login");
        $this->get("/admin/allergens/create")->assertRedirect("/login");
        $this->get("/admin/allergens/{$allergen->id}/edit")->assertRedirect("/login");

        $this->post("/admin/allergens", ["name" => "Alérgeno inyectado"])->assertRedirect("/login");
        $this->put("/admin/allergens/{$allergen->id}", ["name" => "Renombrado sin sesión"])
            ->assertRedirect("/login");
        $this->delete("/admin/allergens/{$allergen->id}")->assertRedirect("/login");

        $this->assertDatabaseMissing("allergens", ["name" => "Alérgeno inyectado"]);
        $this->assertDatabaseHas("allergens", ["id" => $allergen->id, "name" => "Gluten"]);
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->get("/dashboard")->assertRedirect("/login");
    }
}
