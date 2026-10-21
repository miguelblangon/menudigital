<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

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
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => [],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Cappuccino",
            "description" => "Espresso con leche vaporizada y espuma cremosa",
            "price" => 3.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["lacteos"],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Latte",
            "description" => "Espresso con abundante leche vaporizada",
            "price" => 3.80,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["lacteos"],
        ]);

        Product::create([
            "category_id" => $cafes->id,
            "name" => "Americano",
            "description" => "Espresso diluido con agua caliente",
            "price" => 2.80,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => [],
        ]);

        // Tostadas
        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Aguacate",
            "description" => "Pan integral tostado con aguacate fresco y tomate cherry",
            "price" => 6.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten"],
        ]);

        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Tomate",
            "description" => "Pan tostado con tomate, albahaca y aceite de oliva",
            "price" => 5.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten"],
        ]);

        Product::create([
            "category_id" => $tostadas->id,
            "name" => "Tostada de Jamón y Queso",
            "description" => "Pan tostado con jamón serrano y queso fundido",
            "price" => 7.00,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "lacteos"],
        ]);

        // Dulces
        Product::create([
            "category_id" => $dulces->id,
            "name" => "Palmera de Chocolate",
            "description" => "Deliciosa palmera rellena de chocolate",
            "price" => 3.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "lacteos", "huevo"],
        ]);

        Product::create([
            "category_id" => $dulces->id,
            "name" => "Croissant",
            "description" => "Croissant hojaldrado recién horneado",
            "price" => 3.00,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "lacteos", "huevo"],
        ]);

        Product::create([
            "category_id" => $dulces->id,
            "name" => "Croissant de Almendra",
            "description" => "Croissant relleno de crema de almendras",
            "price" => 4.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "lacteos", "huevo", "frutos_cascara"],
        ]);

        // Crepes
        Product::create([
            "category_id" => $crepes->id,
            "name" => "Crepe de Nutella",
            "description" => "Crepe dulce con Nutella y frutas frescas",
            "price" => 5.50,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "lacteos", "huevo", "frutos_cascara"],
        ]);

        Product::create([
            "category_id" => $crepes->id,
            "name" => "Crepe de Frutas",
            "description" => "Crepe con frutas frescas de temporada y miel",
            "price" => 6.00,
            "image" => null, // sin imágenes remotas: subirlas desde el panel (/storage)
            "allergens" => ["gluten", "huevo"],
        ]);
    }
}
