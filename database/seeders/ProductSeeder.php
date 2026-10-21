<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $cafes = Category::where("slug", "cafes")->first();
        $tostadas = Category::where("slug", "tostadas")->first();
        $bolleria = Category::where("slug", "bolleria")->first();
        $tartas = Category::where("slug", "tartas")->first();
        $sandwiches = Category::where("slug", "sandwiches")->first();
        $ensaladas = Category::where("slug", "ensaladas")->first();
        $postres = Category::where("slug", "postres")->first();
        $bebidasFrias = Category::where("slug", "bebidas-frias")->first();

        // CAFÉS (15 productos)
        $cafesProducts = [
            ["name" => "Espresso", "description" => "Café espresso intenso y aromático", "price" => 2.50, "allergens" => []],
            ["name" => "Doble Espresso", "description" => "Doble porción de café espresso", "price" => 3.50, "allergens" => []],
            ["name" => "Americano", "description" => "Espresso diluido con agua caliente", "price" => 2.80, "allergens" => []],
            ["name" => "Cortado", "description" => "Espresso con un toque de leche", "price" => 2.60, "allergens" => ["Lácteos"]],
            ["name" => "Café con Leche", "description" => "Espresso con leche caliente", "price" => 3.00, "allergens" => ["Lácteos"]],
            ["name" => "Cappuccino", "description" => "Espresso con leche vaporizada y espuma cremosa", "price" => 3.50, "allergens" => ["Lácteos"]],
            ["name" => "Latte", "description" => "Espresso con abundante leche vaporizada", "price" => 3.80, "allergens" => ["Lácteos"]],
            ["name" => "Flat White", "description" => "Doble espresso con leche sedosa", "price" => 4.00, "allergens" => ["Lácteos"]],
            ["name" => "Mocha", "description" => "Espresso con chocolate y leche vaporizada", "price" => 4.20, "allergens" => ["Lácteos"]],
            ["name" => "Café Bombón", "description" => "Espresso con leche condensada", "price" => 3.20, "allergens" => ["Lácteos"]],
            ["name" => "Café Irlandés", "description" => "Espresso con whiskey irlandés y crema", "price" => 6.50, "allergens" => ["Lácteos"]],
            ["name" => "Café Turco", "description" => "Café molido fino cocido en cezve", "price" => 3.00, "allergens" => []],
            ["name" => "Espresso Macchiato", "description" => "Espresso con una mancha de espuma", "price" => 2.80, "allergens" => ["Lácteos"]],
            ["name" => "Café descafeinado", "description" => "Espresso sin cafeína", "price" => 2.50, "allergens" => []],
            ["name" => "Red Eye", "description" => "Americano con un shot de espresso", "price" => 3.50, "allergens" => []],
        ];

        foreach ($cafesProducts as $product) {
            $p = Product::create([
                "category_id" => $cafes->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // TOSTADAS (15 productos)
        $tostadasProducts = [
            ["name" => "Tostada de Aguacate", "description" => "Pan integral tostado con aguacate fresco y tomate cherry", "price" => 6.50, "allergens" => ["Gluten"]],
            ["name" => "Tostada de Tomate", "description" => "Pan tostado con tomate, albahaca y aceite de oliva", "price" => 5.50, "allergens" => ["Gluten"]],
            ["name" => "Tostada de Jamón y Queso", "description" => "Pan tostado con jamón serrano y queso fundido", "price" => 7.00, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Tostada Francesa", "description" => "Pan empapado en huevo y leche, dorado a la plancha", "price" => 6.00, "allergens" => ["Gluten", "Huevo", "Lácteos"]],
            ["name" => "Tostada de Salmón", "description" => "Pan tostado con salmón ahumado y queso crema", "price" => 8.50, "allergens" => ["Gluten", "Pescado", "Lácteos"]],
            ["name" => "Tostada de Huevos Revueltos", "description" => "Pan tostado con huevos revueltos y cebollino", "price" => 6.50, "allergens" => ["Gluten", "Huevo"]],
            ["name" => "Tostada de Atún", "description" => "Pan tostado con atún, mayonesa y pepinillos", "price" => 7.00, "allergens" => ["Gluten", "Pescado", "Huevo"]],
            ["name" => "Tostada de Pavo", "description" => "Pan tostado con pavo, lechuga y tomate", "price" => 6.80, "allergens" => ["Gluten"]],
            ["name" => "Tostada de Queso Brie", "description" => "Pan tostado con queso brie y mermelada de frambuesa", "price" => 7.50, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Tostada de Hummus", "description" => "Pan tostado con hummus y verduras frescas", "price" => 6.00, "allergens" => ["Gluten", "Sésamo"]],
            ["name" => "Tostada de Foie", "description" => "Pan tostado con foie gras y mermelada de higos", "price" => 9.50, "allergens" => ["Gluten"]],
            ["name" => "Tostada de Mozzarella", "description" => "Pan tostado con mozzarella fresca y pesto", "price" => 7.00, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Tostada de Bacon", "description" => "Pan tostado con bacon crujiente y huevo frito", "price" => 7.50, "allergens" => ["Gluten", "Huevo"]],
            ["name" => "Tostada de Pollo", "description" => "Pan tostado con pollo a la plancha y mayonesa", "price" => 7.00, "allergens" => ["Gluten", "Huevo"]],
            ["name" => "Tostada Vegetariana", "description" => "Pan tostado con verduras asadas y queso feta", "price" => 6.50, "allergens" => ["Gluten", "Lácteos"]],
        ];

        foreach ($tostadasProducts as $product) {
            $p = Product::create([
                "category_id" => $tostadas->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // BOLLERÍA (20 productos)
        $bolleriaProducts = [
            ["name" => "Croissant", "description" => "Croissant hojaldrado recién horneado", "price" => 3.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Croissant de Almendra", "description" => "Croissant relleno de crema de almendras", "price" => 4.50, "allergens" => ["Gluten", "Lácteos", "Huevo", "Frutos secos"]],
            ["name" => "Croissant de Chocolate", "description" => "Croissant relleno de chocolate", "price" => 3.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Palmera de Chocolate", "description" => "Deliciosa palmera rellena de chocolate", "price" => 3.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Napolitana", "description" => "Bollo dulce con glaseado de azúcar", "price" => 2.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Ensaimada", "description" => "Bollo mallorquín en espiral con azúcar glas", "price" => 4.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Magdalena", "description" => "Bizcochuelo esponjoso individual", "price" => 2.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Magdalena de Chocolate", "description" => "Bizcochuelo con trozos de chocolate", "price" => 3.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Donut Glaseado", "description" => "Donut con glaseado de azúcar", "price" => 2.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Donut de Chocolate", "description" => "Donut relleno de crema de chocolate", "price" => 3.20, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Donut de Fresa", "description" => "Donut con glaseado de fresa", "price" => 3.20, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Eclair de Chocolate", "description" => "Bollo relleno de crema y cobertura de chocolate", "price" => 4.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Eclair de Vainilla", "description" => "Bollo relleno de crema de vainilla", "price" => 3.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Pain au Chocolat", "description" => "Hojaldre francés con chocolate", "price" => 3.50, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Galleta de Mantequilla", "description" => "Galleta artesanal de mantequilla", "price" => 1.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Galleta de Chocolate", "description" => "Galleta con trozos de chocolate", "price" => 2.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Cookie Americana", "description" => "Cookie grande con chips de chocolate", "price" => 3.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Muffin de Arándanos", "description" => "Muffin esponjoso con arándanos frescos", "price" => 3.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Muffin de Chocolate", "description" => "Muffin con trozos de chocolate", "price" => 3.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Canelazo", "description" => "Bollo dulce en forma de canela con glaseado", "price" => 3.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
        ];

        foreach ($bolleriaProducts as $product) {
            $p = Product::create([
                "category_id" => $bolleria->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // TARTAS (15 productos)
        $tartasProducts = [
            ["name" => "Tarta de Chocolate", "description" => "Bizcocho de chocolate con ganache", "price" => 4.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Queso", "description" => "Tarta cremosa de queso con base de galleta", "price" => 4.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Fresas", "description" => "Bizcocho con crema y fresas frescas", "price" => 5.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Limón", "description" => "Tarta ácida de limón con merengue", "price" => 4.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Manzana", "description" => "Bizcocho con manzanas caramelizadas", "price" => 4.20, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Arándanos", "description" => "Bizcocho con arándanos y crema", "price" => 4.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Caramelo", "description" => "Tarta cremosa de caramelo salado", "price" => 5.00, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Frutos Rojos", "description" => "Bizcocho con frutos rojos y crema", "price" => 5.20, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Coco", "description" => "Tarta cremosa de coco rallado", "price" => 4.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Avellana", "description" => "Tarta con crema de avellanas", "price" => 5.00, "allergens" => ["Gluten", "Lácteos", "Huevo", "Frutos secos"]],
            ["name" => "Tarta de Nutella", "description" => "Bizcocho con relleno de Nutella", "price" => 4.80, "allergens" => ["Gluten", "Lácteos", "Huevo", "Frutos secos"]],
            ["name" => "Tarta de Plátano", "description" => "Bizcocho con plátanos caramelizados", "price" => 4.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Pistacho", "description" => "Tarta cremosa de pistacho", "price" => 5.50, "allergens" => ["Gluten", "Lácteos", "Huevo", "Frutos secos"]],
            ["name" => "Tarta de Maracuyá", "description" => "Tarta ácida de maracuyá con crema", "price" => 5.20, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Tarta de Menta y Chocolate", "description" => "Bizcocho de chocolate con crema de menta", "price" => 4.80, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
        ];

        foreach ($tartasProducts as $product) {
            $p = Product::create([
                "category_id" => $tartas->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // SANDWICHES (15 productos)
        $sandwichesProducts = [
            ["name" => "Sandwich de Jamón y Queso", "description" => "Pan de molde con jamón y queso fundido", "price" => 6.50, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Sandwich de Pollo", "description" => "Pollo a la plancha con lechuga y tomate", "price" => 7.50, "allergens" => ["Gluten"]],
            ["name" => "Sandwich de Atún", "description" => "Atún con mayonesa, pepinillos y cebolla", "price" => 7.00, "allergens" => ["Gluten", "Pescado", "Huevo"]],
            ["name" => "Sandwich de Pavo", "description" => "Pavo con queso suizo y mostaza", "price" => 7.50, "allergens" => ["Gluten", "Lácteos", "Mostaza"]],
            ["name" => "Sandwich Vegetariano", "description" => "Verduras frescas, hummus y queso feta", "price" => 6.80, "allergens" => ["Gluten", "Lácteos", "Sésamo"]],
            ["name" => "Sandwich de Bacon", "description" => "Bacon crujiente con huevo y queso", "price" => 8.00, "allergens" => ["Gluten", "Huevo", "Lácteos"]],
            ["name" => "Sandwich de Salmón", "description" => "Salmón ahumado con queso crema y alcaparras", "price" => 9.00, "allergens" => ["Gluten", "Pescado", "Lácteos"]],
            ["name" => "Sandwich de Tortilla", "description" => "Tortilla española con tomate y aceite", "price" => 6.50, "allergens" => ["Gluten", "Huevo"]],
            ["name" => "Sandwich de Embutidos", "description" => "Mix de embutidos ibéricos con queso", "price" => 8.50, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Sandwich de Queso Fundido", "description" => "Queso fundido con tomate y cebolla caramelizada", "price" => 6.00, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Sandwich de Pulpo", "description" => "Pulpo a la gallega con patata y pimentón", "price" => 9.50, "allergens" => ["Gluten", "Moluscos"]],
            ["name" => "Sandwich de Cordero", "description" => "Cordero asado con tzatziki y verduras", "price" => 9.00, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Sandwich de Ternera", "description" => "Ternera a la plancha con salsa barbacoa", "price" => 9.50, "allergens" => ["Gluten"]],
            ["name" => "Sandwich de Pechuga", "description" => "Pechuga de pollo con pesto y mozzarella", "price" => 8.00, "allergens" => ["Gluten", "Lácteos"]],
            ["name" => "Sandwich Mixto", "description" => "Jamón cocido, queso y tomate", "price" => 6.50, "allergens" => ["Gluten", "Lácteos"]],
        ];

        foreach ($sandwichesProducts as $product) {
            $p = Product::create([
                "category_id" => $sandwiches->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // ENSALADAS (10 productos)
        $ensaladasProducts = [
            ["name" => "Ensalada César", "description" => "Lechuga romana, crutones, parmesano y salsa césar", "price" => 8.50, "allergens" => ["Gluten", "Lácteos", "Huevo"]],
            ["name" => "Ensalada Mixta", "description" => "Mezcla de lechugas con tomate y pepino", "price" => 6.50, "allergens" => []],
            ["name" => "Ensalada Griega", "description" => "Tomate, pepino, cebolla, aceitunas y queso feta", "price" => 8.00, "allergens" => ["Lácteos"]],
            ["name" => "Ensalada de Pollo", "description" => "Pollo a la plancha con verduras frescas", "price" => 9.50, "allergens" => []],
            ["name" => "Ensalada de Atún", "description" => "Atún fresco con ensalada mixta", "price" => 9.50, "allergens" => ["Pescado"]],
            ["name" => "Ensalada Caprese", "description" => "Mozzarella, tomate y albahaca fresca", "price" => 8.50, "allergens" => ["Lácteos"]],
            ["name" => "Ensalada de Quinoa", "description" => "Quinoa con verduras y aderezo cítrico", "price" => 9.00, "allergens" => []],
            ["name" => "Ensalada de Salmón", "description" => "Salmón ahumado con ensalada verde", "price" => 10.50, "allergens" => ["Pescado"]],
            ["name" => "Ensalada de Garbanzos", "description" => "Garbanzos con verduras y alioli", "price" => 7.50, "allergens" => ["Huevo"]],
            ["name" => "Ensalada Waldorf", "description" => "Manzana, apio, nueces y aderezo cremoso", "price" => 8.50, "allergens" => ["Frutos secos", "Lácteos"]],
        ];

        foreach ($ensaladasProducts as $product) {
            $p = Product::create([
                "category_id" => $ensaladas->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // POSTRES (5 productos)
        $postresProducts = [
            ["name" => "Flan de Vainilla", "description" => "Flan casero con caramelo", "price" => 4.00, "allergens" => ["Huevo", "Lácteos"]],
            ["name" => "Tiramisú", "description" => "Postre italiano con café y mascarpone", "price" => 5.50, "allergens" => ["Gluten", "Huevo", "Lácteos"]],
            ["name" => "Brownie", "description" => "Brownie de chocolate con nueces", "price" => 4.50, "allergens" => ["Gluten", "Huevo", "Lácteos", "Frutos secos"]],
            ["name" => "Helado Artesanal", "description" => "Helado artesanal (3 bolas)", "price" => 5.00, "allergens" => ["Lácteos"]],
            ["name" => "Crema Catalana", "description" => "Postre cremoso con azúcar caramelizado", "price" => 4.50, "allergens" => ["Huevo", "Lácteos"]],
        ];

        foreach ($postresProducts as $product) {
            $p = Product::create([
                "category_id" => $postres->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }

        // BEBIDAS FRÍAS (5 productos)
        $bebidasFriasProducts = [
            ["name" => "Café Frío", "description" => "Espresso servido frío con hielo", "price" => 3.50, "allergens" => []],
            ["name" => "Frappuccino de Café", "description" => "Bebida helada de café con leche y hielo", "price" => 5.00, "allergens" => ["Lácteos"]],
            ["name" => "Frappuccino de Chocolate", "description" => "Bebida helada de chocolate con leche", "price" => 5.00, "allergens" => ["Lácteos"]],
            ["name" => "Smoothie de Frutas", "description" => "Batido de frutas frescas de temporada", "price" => 5.50, "allergens" => []],
            ["name" => "Limonada Natural", "description" => "Limonada casera con hierbabuena", "price" => 3.50, "allergens" => []],
        ];

        foreach ($bebidasFriasProducts as $product) {
            $p = Product::create([
                "category_id" => $bebidasFrias->id,
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
            ]);
            if (! empty($product["allergens"])) {
                $this->attachAllergens($p, $product["allergens"]);
            }
        }
    }

    private function attachAllergens(Product $product, array $allergenNames): void
    {
        foreach ($allergenNames as $name) {
            $alergen = Allergen::firstOrCreate(["name" => $name]);
            $product->allergens()->attach($alergen);
        }
    }
}
