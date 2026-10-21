<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Allergen;

class AlergenSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            "Gluten",
            "Lácteos",
            "Huevo",
            "Frutos secos",
            "Frutos de cáscara",
            "Sésamo",
            "Soja",
            "Apio",
            "Mostaza",
            "Altramuces",
            "Pescado",
            "Crustáceos",
            "Moluscos",
            "Sulfitos",
        ];

        foreach ($allergens as $name) {
            Allergen::create(["name" => $name]);
        }
    }
}