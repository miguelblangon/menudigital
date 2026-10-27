<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->actingAs(User::factory()->create());
    }

    // INDEX
    public function test_index_returns_products()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Croquetas',
            'price' => 8.50,
            'available' => true,
        ]);

        $response = $this->get('/admin/products');

        $response->assertViewIs('admin.products.index');
        $response->assertViewHas('products', function ($products) {
            return $products->count() === 1 && $products->first()->name === 'Croquetas';
        });
    }

    public function test_index_loads_category_relation()
    {
        $category = Category::create(['name' => 'Postres', 'slug' => 'postres']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Tarta',
            'price' => 5.00,
        ]);

        $response = $this->get('/admin/products');

        $response->assertViewHas('products', function ($products) {
            return $products->first()->relationLoaded('category');
        });
    }

    // INDEX · FILTROS
    public function test_index_filters_by_product_name()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas de jamón', 'price' => 8.50]);
        Product::create(['category_id' => $category->id, 'name' => 'Ensalada templada', 'price' => 11.00]);

        $response = $this->get('/admin/products?search=croq');

        $response->assertOk();
        $response->assertViewHas('products', function ($products) {
            return $products->count() === 1 && $products->first()->name === 'Croquetas de jamón';
        });
        $response->assertViewHas('search', 'croq');
        $response->assertViewHas('filtersActive', true);
        $response->assertSee('Croquetas de jamón')->assertDontSee('Ensalada templada');
    }

    public function test_index_name_search_ignores_case_but_accents_are_literal()
    {
        $category = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas']);
        Product::create(['category_id' => $category->id, 'name' => 'Café solo', 'price' => 1.60]);
        Product::create(['category_id' => $category->id, 'name' => 'AGUA', 'price' => 1.20]);

        // SQLite resuelve LIKE sin distinguir mayúsculas en ASCII...
        $this->get('/admin/products?search=agua')
            ->assertViewHas('products', fn ($products) => $products->count() === 1);

        // ...pero el acento es literal: "cafe" no encuentra "Café". El usuario lo
        // hereda del motor, no es un fallo del filtro.
        $this->get('/admin/products?search=cafe')
            ->assertViewHas('products', fn ($products) => $products->isEmpty());
    }

    public function test_index_filters_by_category()
    {
        $entradas = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $postres = Category::create(['name' => 'Postres', 'slug' => 'postres']);
        Product::create(['category_id' => $entradas->id, 'name' => 'Croquetas', 'price' => 8.50]);
        Product::create(['category_id' => $postres->id, 'name' => 'Tarta', 'price' => 5.00]);

        $response = $this->get("/admin/products?category={$postres->id}");

        $response->assertViewHas('products', fn ($products) => $products->count() === 1
            && $products->first()->name === 'Tarta');
        $response->assertViewHas('categoryId', $postres->id);
    }

    public function test_index_combines_name_and_category()
    {
        $entradas = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $postres = Category::create(['name' => 'Postres', 'slug' => 'postres']);
        Product::create(['category_id' => $entradas->id, 'name' => 'Tarta de Santiago', 'price' => 6.00]);
        Product::create(['category_id' => $postres->id, 'name' => 'Tarta de queso', 'price' => 5.00]);

        $response = $this->get("/admin/products?search=tarta&category={$postres->id}");

        $response->assertViewHas('products', fn ($products) => $products->count() === 1
            && $products->first()->name === 'Tarta de queso');
    }

    public function test_index_ignores_blank_and_unknown_filters()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);

        // Espacios en blanco: no es un filtro, es la nada.
        $this->get('/admin/products?search=+')
            ->assertViewHas('products', fn ($products) => $products->count() === 1)
            ->assertViewHas('search', null)
            ->assertViewHas('filtersActive', false);

        // Una categoría borrada con la pestaña abierta debe dejar el listado
        // completo, no vaciarlo: un filtro que no aplica a nada no es un error.
        $this->get('/admin/products?category=9999')
            ->assertViewHas('products', fn ($products) => $products->count() === 1)
            ->assertViewHas('categoryId', null);
    }

    public function test_index_keeps_the_filter_values_so_the_form_is_not_lost()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);

        $this->get("/admin/products?search=croq&category={$category->id}")
            ->assertSee('value="croq"', false)
            ->assertSee('value="'.$category->id.'" selected', false);
    }

    // FILTERS · el fragmento que pide Alpine al teclear
    public function test_filters_endpoint_returns_only_the_list_partial()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);
        Product::create(['category_id' => $category->id, 'name' => 'Ensalada', 'price' => 11.00]);

        $response = $this->get('/admin/products/filters?search=croq');

        $response->assertOk();
        $response->assertViewIs('admin.products.partials.list');
        $response->assertViewHas('products', fn ($products) => $products->count() === 1
            && $products->first()->name === 'Croquetas');

        // Es el fragmento, no la página: si devolviera el layout completo, Alpine
        // lo inyectaría dentro del contenedor y el panel se duplicaría.
        $html = $response->getContent();
        $this->assertStringNotContainsString('<!DOCTYPE html>', $html);
        $this->assertStringNotContainsString('id="sidebar"', $html);
    }

    public function test_filters_endpoint_matches_what_index_rendered()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);

        $query = "?search=croq&category={$category->id}";

        $page = $this->get('/admin/products'.$query)->getContent();
        $partial = trim($this->get('/admin/products/filters'.$query)->getContent());

        // El primer render de Blade y el fragmento de Alpine tienen que ser el
        // mismo HTML: si se bifurcan, el listado cambia al teclear la primera letra.
        $this->assertStringContainsString($partial, $page);
    }

    public function test_filters_endpoint_shows_the_empty_state_with_filters_active()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);

        $this->get('/admin/products/filters?search=inexistente')
            ->assertOk()
            ->assertSee('Ningún producto coincide con la búsqueda.');
    }

    public function test_filters_endpoint_is_not_eaten_by_the_show_route()
    {
        // `admin/products/filters` se declara antes del resource: registrada
        // después, el route model binding se traga la palabra y responde 404.
        $this->get('/admin/products/filters')->assertOk();
    }

    public function test_filters_endpoint_ships_delete_forms_that_still_work()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create(['category_id' => $category->id, 'name' => 'Croquetas', 'price' => 8.50]);

        $html = $this->get('/admin/products/filters')->getContent();

        // El fragmento se inyecta con innerHTML: los @csrf del borrado tienen que
        // venir dentro, o borrar desde el listado filtrado mandaría token vacío.
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);

        // Y ningún nodo del fragmento puede traer directivas de Alpine: innerHTML
        // no reequilibra el árbol, así que quedarían inertes.
        $this->assertStringNotContainsString('x-for=', $html);
        $this->assertStringNotContainsString('x-show=', $html);
        $this->assertStringNotContainsString('onclick=', $html);
    }

    public function test_filters_endpoint_escapes_product_names()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Product::create([
            'category_id' => $category->id,
            'name' => '<script>alert(1)</script>',
            'price' => 8.50,
        ]);

        $html = $this->get('/admin/products/filters?search=script')->getContent();

        // El fragmento viaja por innerHTML: si Blade no lo escapase, el refresco
        // sería un XSS aunque la página original estuviera limpia.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    // CREATE
    public function test_create_returns_categories()
    {
        Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        Category::create(['name' => 'Principales', 'slug' => 'principales']);

        $response = $this->get('/admin/products/create');

        $response->assertViewIs('admin.products.create');
        $response->assertViewHas('categories', function ($categories) {
            return $categories->count() === 2;
        });
    }

    // STORE
    public function test_store_creates_product()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);

        $response = $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Ensalada',
            'description' => 'Ensalada fresca',
            'price' => 12.50,
            'available' => '1',
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => 'Ensalada',
            'price' => 12.50,
            'available' => true,
        ]);
    }

    public function test_store_can_mark_product_unavailable()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);

        $response = $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Especial de la casa',
            'price' => 18.00,
            'available' => '0',
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'name' => 'Especial de la casa',
            'available' => false,
        ]);
    }

    public function test_store_rejects_allergen_id_that_does_not_exist()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);

        $response = $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Pan',
            'price' => 1.00,
            'allergens' => [999],
        ]);

        $response->assertSessionHasErrors('allergens.0');
        $this->assertSame(0, Product::count());
    }

    public function test_store_with_image()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Producto con imagen',
            'price' => 10.00,
            'image' => $file,
        ]);

        $response->assertRedirect('/admin/products');
        Storage::disk('public')->assertExists('products/'.$file->hashName());
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post('/admin/products', []);

        $response->assertSessionHasErrors(['category_id', 'name', 'price']);
    }

    public function test_store_validates_category_exists()
    {
        $response = $this->post('/admin/products', [
            'category_id' => 999,
            'name' => 'Producto',
            'price' => 10.00,
        ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    public function test_store_validates_price_is_numeric()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);

        $response = $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Producto',
            'price' => 'abc',
        ]);

        $response->assertSessionHasErrors(['price']);
    }

    // EDIT
    public function test_edit_shows_form_with_product()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto a editar',
            'price' => 15.00,
        ]);

        $response = $this->get("/admin/products/{$product->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.products.edit');
        $response->assertViewHas('product', function ($p) use ($product) {
            return $p->id === $product->id;
        });
        $response->assertViewHas('categories');
    }

    // UPDATE
    public function test_update_modifies_product()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nombre original',
            'price' => 10.00,
        ]);

        $response = $this->put("/admin/products/{$product->id}", [
            'category_id' => $category->id,
            'name' => 'Nombre actualizado',
            'price' => 20.00,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nombre actualizado',
            'price' => 20.00,
        ]);
    }

    public function test_update_with_new_image_deletes_old()
    {
        Storage::disk('public')->put('products/old-image.jpg', 'content');

        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto',
            'price' => 10.00,
            'image' => 'products/old-image.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new-image.jpg');

        $response = $this->put("/admin/products/{$product->id}", [
            'category_id' => $category->id,
            'name' => 'Producto',
            'price' => 10.00,
            'image' => $newFile,
        ]);

        $response->assertRedirect('/admin/products');
        Storage::disk('public')->assertMissing('products/old-image.jpg');
        Storage::disk('public')->assertExists('products/'.$newFile->hashName());
    }

    public function test_update_without_image_keeps_existing()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto',
            'price' => 10.00,
            'image' => 'products/existing.jpg',
        ]);

        $response = $this->put("/admin/products/{$product->id}", [
            'category_id' => $category->id,
            'name' => 'Producto actualizado',
            'price' => 15.00,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image' => 'products/existing.jpg',
        ]);
    }

    // DESTROY
    public function test_destroy_deletes_product()
    {
        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto a eliminar',
            'price' => 10.00,
        ]);

        $response = $this->delete("/admin/products/{$product->id}");

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_destroy_deletes_image()
    {
        Storage::disk('public')->put('products/to-delete.jpg', 'content');

        $category = Category::create(['name' => 'Entradas', 'slug' => 'entradas']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto',
            'price' => 10.00,
            'image' => 'products/to-delete.jpg',
        ]);

        $response = $this->delete("/admin/products/{$product->id}");

        $response->assertRedirect('/admin/products');
        Storage::disk('public')->assertMissing('products/to-delete.jpg');
    }
}
