<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Categorías
        $cafes = Category::create(["name" => "Cafés", "slug" => "cafes"]);
        $tostadas = Category::create(["name" => "Tostadas", "slug" => "tostadas"]);
        $dulces = Category::create(["name" => "Dulces", "slug" => "dulces"]);
        $crepes = Category::create(["name" => "Crepes", "slug" => "crepes"]);

        // Cafés
        Product::create([
            "category_id" => $cafes->id,
            "name" => "Espresso",
            "description" => "Café espresso intenso y aromático",
            "price" => 2.50,
            "image" => "https://images.unsplash.com/photo-1510707577280-9701049b44d8?w=400",
            "allergens" => [],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Cappuccino",
            "description" => "Espresso con leche vaporizada y espuma cremosa",
            "price" => 3.50,
            "image" => "https://images.unsplash.com/photo-1572449044476-3f3d9a29f03c?w=400",
            "allergens" => ["lacteos"],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Latte",
            "description" => "Espresso con abundante leche vaporizada",
            "price" => 3.80,
            "image" => "https://images.unsplash.com/photo-1464286999887-6f6cc5b5e3ff?w=400",
            "allergens" => ["lacteos"],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Americano",
            "description" => "Espresso diluido con agua caliente",
            "price" => 2.80,
            "image" => "https://images.unsplash.com/photo-1495474402881-8b6f3e2a6c97?w=400",
            "allergens" => [],
        ]);

        // Tostadas
        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Aguacate",
            "description" => "Pan integral tostado con aguacate fresco y tomate cherry",
            "price" => 6.50,
            "image" => "https://images.unsplash.com/photo-1541857067749-a6a5f0fce786?w=400",
            "allergens" => ["gluten"],
        ]);

        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Tomate",
            "description" => "Pan tostado con tomate, albahaca y aceite de oliva",
            "price" => 5.50,
            "image" => "https://images.unsplash.com/photo-1608039764278-8f2d2e3b4e5a?w=400",
            "allergens" => ["gluten"],
        ]);

        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Jamón y Queso",
            "description" => "Pan tostado con jamón serrano y queso fundido",
            "price" => 7.00,
            "image" => "https://images.unsplash.com/photo-1529394498760-fc4f3e74b8aa?w=400",
            "allergens" => ["gluten", "lacteos"],
        ]);

        // Dulces
        Product::create([
            "category_id" => $dulces->id,
            "name" => "Palmera de Chocolate",
            "description" => "Deliciosa palmera rellena de chocolate",
            "price" => 3.50,
            "image" => "https://images.unsplash.com/photo-1558961363-fa8fdf8eddb4?w=400",
            "allergens" => ["gluten", "lacteos", "huevo"],
        ]);

        Product::create([
            "category_id" => $dulces->id,
            "name" => "Croissant",
            "description" => "Croissant hojaldrado recién horneado",
            "price" => 3.00,
            "image" => "https://images.unsplash.com/photo-1555507023-ab8c4cee5b33?w=400",
            "allergens" => ["gluten", "lacteos", "huevo"],
        ]);

        Product::create([
            "category_id" => $dulces->id,
            "name" => "Croissant de Almendra",
            "description" => "Croissant relleno de crema de almendras",
            "price" => 4.50,
            "image" => "https://images.unsplash.com/photo-1509365763045-97c61db44cc3?w=400",
            "allergens" => ["gluten", "lacteos", "huevo", "frutos_cascara"],
        ]);

        // Crepes
        Product::create([
            "category_id" => $crepes->id,
            "name" => "Crepe de Nutella",
            "description" => "Crepe dulce con Nutella y frutas frescas",
            "price" => 5.50,
            "image" => "https://images.unsplash.com/photo-1519690899828-fd93a92300e3?w=400",
            "allergens" => ["gluten", "lacteos", "huevo", "frutos_cascara"],
        ]);

        Product::create([
            "category_id" => $crepes->id,
            "name" => "Crepe de Frutas",
            "description" => "Crepe con frutas frescas de temporada y miel",
            "price" => 6.00,
            "image" => "https://images.unsplash.com/photo-1565958010727-456d2f3daecf?w=400",
            "allergens" => ["gluten", "huevo"],
        ]);
    }
}