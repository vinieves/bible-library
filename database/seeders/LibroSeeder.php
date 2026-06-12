<?php

namespace Database\Seeders;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Category;
use App\Models\Material;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LibroSeeder extends Seeder
{
    public function run(): void
    {
        $planCompleto = Plan::query()->where('slug', 'completo')->firstOrFail();

        $libros = [
            [
                'title' => 'El Pentateuco Explicado',
                'category' => 'pentateuco',
                'cover' => 'covers/libros/el-pentateuco',
                'description' => 'Génesis, Éxodo, Levítico, Números y Deuteronomio explicados versículo por versículo.',
                'order' => 1,
            ],
            [
                'title' => 'Los 4 Evangelios Explicados',
                'category' => 'evangelios',
                'cover' => 'covers/libros/los-4-evangelicos',
                'description' => 'Mateo, Marcos, Lucas y Juan con explicación clara de cada versículo.',
                'order' => 2,
            ],
            [
                'title' => 'Hechos de los Apóstoles Explicados',
                'category' => 'hechos-de-los-apostoles',
                'cover' => 'covers/libros/los-apostoles',
                'description' => 'La historia de la iglesia primitiva explicada paso a paso.',
                'order' => 3,
            ],
            [
                'title' => '13 Cartas de Pablo Explicadas',
                'category' => 'cartas-de-pablo',
                'cover' => 'covers/libros/13-cartas',
                'description' => 'Todas las epístolas de Pablo con contexto y aplicación práctica.',
                'order' => 4,
            ],
            [
                'title' => '150 Salmos Explicados',
                'category' => 'salmos-y-proverbios',
                'cover' => 'covers/libros/150-salmos',
                'description' => 'Cada salmo explicado para enriquecer su vida de oración y estudio.',
                'order' => 5,
            ],
            [
                'title' => '31 Proverbios Explicados',
                'category' => 'salmos-y-proverbios',
                'cover' => 'covers/libros/31-proverbios',
                'description' => 'Sabiduría bíblica para el día a día, versículo por versículo.',
                'order' => 6,
            ],
            [
                'title' => 'El Apocalipsis Explicado',
                'category' => 'apocalipsis',
                'cover' => 'covers/libros/el-apocalipsis',
                'description' => 'El libro del Apocalipsis con explicación accesible y profunda.',
                'order' => 7,
            ],
        ];

        $keepSlugs = collect($libros)->map(fn ($libro) => Str::slug($libro['title']))->all();

        Material::query()
            ->where('type', MaterialType::Libro)
            ->whereNotIn('slug', $keepSlugs)
            ->delete();

        foreach ($libros as $libro) {
            $category = Category::query()->where('slug', $libro['category'])->firstOrFail();

            Material::query()->updateOrCreate(
                ['slug' => Str::slug($libro['title'])],
                [
                    'category_id' => $category->id,
                    'plan_id' => $planCompleto->id,
                    'title' => $libro['title'],
                    'description' => $libro['description'],
                    'cover_image' => $libro['cover'],
                    'type' => MaterialType::Libro,
                    'pdf_path' => null,
                    'content' => null,
                    'status' => MaterialStatus::Published,
                    'sort_order' => $libro['order'],
                ]
            );
        }
    }
}
