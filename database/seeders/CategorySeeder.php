<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ["name" => "Cafés", "slug" => "cafes"],
            ["name" => "Tostadas", "slug" => "tostadas"],
            ["name" => "Bollería", "slug" => "bolleria"],
            ["name" => "Tartas", "slug" => "tartas"],
            ["name" => "Sandwiches", "slug" => "sandwiches"],
            ["name" => "Ensaladas", "slug" => "ensaladas"],
            ["name" => "Postres", "slug" => "postres"],
            ["name" => "Bebidas Frías", "slug" => "bebidas-frias"],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}