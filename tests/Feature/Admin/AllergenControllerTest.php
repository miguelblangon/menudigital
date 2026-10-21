<?php

namespace Tests\Feature\Admin;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllergenControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    // INDEX
    public function test_index_renders_allergens_page()
    {
        Allergen::create(['name' => 'Gluten']);
        Allergen::create(['name' => 'Lactosa']);

        $response = $this->get('/admin/allergens');

        $response->assertStatus(200);
        $response->assertViewIs('admin.allergens.index');
        $response->assertViewHas('allergens', function ($allergens) {
            return $allergens->count() === 2;
        });
    }

    public function test_index_uses_the_allergen_product_pivot_table()
    {
        $category = Category::create(['name' => 'Principales', 'slug' => 'principales']);
        $allergen = Allergen::create(['name' => 'Gluten']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pan',
            'price' => 3.00,
        ]);

        $allergen->products()->attach($product);

        $this->assertDatabaseHas('allergen_product', [
            'allergen_id' => $allergen->id,
            'product_id' => $product->id,
        ]);

        $response = $this->get('/admin/allergens');

        $response->assertViewHas('allergens', function ($allergens) {
            return $allergens->first()->products_count === 1;
        });
    }

    public function test_relation_is_reversible_from_both_sides()
    {
        $category = Category::create(['name' => 'Principales', 'slug' => 'principales']);
        $allergen = Allergen::create(['name' => 'Gluten']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Crackers',
            'price' => 2.50,
        ]);

        $product->allergens()->attach($allergen);

        $this->assertSame(1, $product->allergens()->count());
        $this->assertSame(1, $allergen->products()->count());
    }

    // CREATE
    public function test_create_returns_the_allergen_form()
    {
        $response = $this->get('/admin/allergens/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.allergens.create');
        $response->assertSee('Crear Alérgeno');
    }

    // STORE
    public function test_store_creates_allergen()
    {
        $response = $this->post('/admin/allergens', ['name' => 'Gluten']);

        $response->assertRedirect('/admin/allergens');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('allergens', ['name' => 'Gluten']);
    }

    public function test_store_requires_a_name()
    {
        $response = $this->post('/admin/allergens', []);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Allergen::count());
    }

    public function test_store_rejects_duplicate_name()
    {
        Allergen::create(['name' => 'Gluten']);

        $response = $this->post('/admin/allergens', ['name' => 'Gluten']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Allergen::count());
    }

    // EDIT
    public function test_edit_renders_the_allergen_form()
    {
        $allergen = Allergen::create(['name' => 'Gluten']);

        $response = $this->get("/admin/allergens/{$allergen->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.allergens.edit');
        $response->assertSee('Editar Alérgeno');
        $response->assertSee('Gluten');
    }

    // UPDATE
    public function test_update_renames_allergen()
    {
        $allergen = Allergen::create(['name' => 'Gluten']);

        $response = $this->put("/admin/allergens/{$allergen->id}", [
            'name' => 'Contains gluten',
        ]);

        $response->assertRedirect('/admin/allergens');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('allergens', [
            'id' => $allergen->id,
            'name' => 'Contains gluten',
        ]);
    }

    public function test_update_allows_keeping_the_current_name()
    {
        $allergen = Allergen::create(['name' => 'Gluten']);

        $response = $this->put("/admin/allergens/{$allergen->id}", [
            'name' => 'Gluten',
        ]);

        $response->assertRedirect('/admin/allergens');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('allergens', [
            'id' => $allergen->id,
            'name' => 'Gluten',
        ]);
    }

    public function test_update_rejects_name_taken_by_other_allergen()
    {
        Allergen::create(['name' => 'Gluten']);
        $allergen = Allergen::create(['name' => 'Huevo']);

        $response = $this->put("/admin/allergens/{$allergen->id}", [
            'name' => 'Gluten',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('allergens', [
            'id' => $allergen->id,
            'name' => 'Huevo',
        ]);
    }

    // DESTROY
    public function test_destroy_deletes_allergen()
    {
        $allergen = Allergen::create(['name' => 'Gluten']);

        $response = $this->delete("/admin/allergens/{$allergen->id}");

        $response->assertRedirect('/admin/allergens');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('allergens', ['id' => $allergen->id]);
    }

    public function test_destroy_detaches_linked_products()
    {
        $category = Category::create(['name' => 'Principales', 'slug' => 'principales']);
        $allergen = Allergen::create(['name' => 'Gluten']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pan',
            'price' => 3.00,
        ]);

        $allergen->products()->attach($product);

        $response = $this->delete("/admin/allergens/{$allergen->id}");

        $response->assertRedirect('/admin/allergens');
        $this->assertDatabaseMissing('allergen_product', [
            'allergen_id' => $allergen->id,
            'product_id' => $product->id,
        ]);
    }
}

