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
            'is_available' => true,
            'allergens' => [],
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
            'allergens' => [],
        ]);

        $response = $this->get('/admin/products');

        $response->assertViewHas('products', function ($products) {
            return $products->first()->relationLoaded('category');
        });
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
            'is_available' => true,
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => 'Ensalada',
            'price' => 12.50,
            'is_available' => true,
        ]);
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
        Storage::disk('public')->assertExists('products/' . $file->hashName());
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
        Storage::disk('public')->assertExists('products/' . $newFile->hashName());
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