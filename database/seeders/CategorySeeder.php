<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pentateuco', 'icon' => 'book-open', 'sort_order' => 1],
            ['name' => 'Evangelios', 'icon' => 'cross', 'sort_order' => 2],
            ['name' => 'Salmos y Proverbios', 'icon' => 'music', 'sort_order' => 3],
            ['name' => 'Cartas de Pablo', 'icon' => 'mail', 'sort_order' => 4],
            ['name' => 'Apocalipsis', 'icon' => 'star', 'sort_order' => 5],
            ['name' => 'Hechos de los Apóstoles', 'icon' => 'users', 'sort_order' => 6],
            ['name' => 'Bonos', 'icon' => 'gift', 'sort_order' => 7],
            ['name' => 'Mapas Mentales', 'icon' => 'map', 'sort_order' => 8],
            ['name' => 'Devocionales', 'icon' => 'heart', 'sort_order' => 9],
            ['name' => 'Estudios Premium', 'icon' => 'crown', 'sort_order' => 10],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => 'Materiales de la categoría '.$category['name'].'.',
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
