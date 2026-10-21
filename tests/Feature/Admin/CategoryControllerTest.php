<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_can_view_categories_index()
    {
        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.index');
        $response->assertSee('Categorías');
    }

    public function test_can_view_create_form()
    {
        $response = $this->get(route('admin.categories.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.create');
        $response->assertSee('Crear Categoría');
    }

    public function test_can_create_category()
    {
        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Entradas',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['name' => 'Entradas', 'slug' => 'entradas']);
    }

    public function test_category_name_must_be_unique()
    {
        Category::create(['name' => 'Postres', 'slug' => 'postres']);

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Postres',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_view_edit_form()
    {
        $category = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas']);

        $response = $this->get(route('admin.categories.edit', $category));
        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.edit');
        $response->assertSee('Editar Categoría');
        $response->assertSee('Bebidas');
    }

    public function test_can_update_category()
    {
        $category = Category::create(['name' => 'Snacks', 'slug' => 'snacks']);

        $response = $this->put(route('admin.categories.update', $category), [
            'name' => 'Picoteo',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Picoteo', 'slug' => 'picoteo']);
    }

    public function test_can_delete_category()
    {
        $category = Category::create(['name' => 'Tapas', 'slug' => 'tapas']);

        $response = $this->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}