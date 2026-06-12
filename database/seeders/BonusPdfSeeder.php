<?php

namespace Database\Seeders;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Category;
use App\Models\Material;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BonusPdfSeeder extends Seeder
{
    public function run(): void
    {
        $planBasico = Plan::query()->where('slug', 'basico')->firstOrFail();
        $category = Category::query()->where('slug', 'bonos')->firstOrFail();

        $bonuses = [
            [
                'title' => 'Los 10 Mandamientos Explicados',
                'pdf' => 'pdfs/bonuses/bonus1-mandamentos.pdf',
                'cover' => 'covers/bonuses/img-bonus-1',
                'description' => 'Guía clara sobre los diez mandamientos, explicados versículo por versículo.',
                'order' => 1,
            ],
            [
                'title' => 'Milagros de Jesús Explicados',
                'pdf' => 'pdfs/bonuses/bonus2-milagres.pdf',
                'cover' => 'covers/bonuses/img-bonus-2',
                'description' => 'Los milagros de Jesús con contexto bíblico y aplicación práctica.',
                'order' => 2,
            ],
            [
                'title' => 'Historias Bíblicas Memorables',
                'pdf' => 'pdfs/bonuses/bonus3-historias.pdf',
                'cover' => 'covers/bonuses/img-bonus-3',
                'description' => 'Las historias más importantes de la Biblia explicadas de forma sencilla.',
                'order' => 3,
            ],
            [
                'title' => 'Devocional Bíblico Premium',
                'pdf' => 'pdfs/bonuses/bonus4-devocional.pdf',
                'cover' => 'covers/bonuses/img-bonus-4',
                'description' => 'Devocional para fortalecer su vida espiritual día a día.',
                'order' => 4,
            ],
            [
                'title' => 'Manual de Estudio Bíblico',
                'pdf' => 'pdfs/bonuses/bonus5-manual.pdf',
                'cover' => 'covers/bonuses/img-bonus-5',
                'description' => 'Manual práctico para estudiar la Biblia con método y profundidad.',
                'order' => 5,
            ],
        ];

        $keepSlugs = collect($bonuses)->map(fn ($bonus) => Str::slug($bonus['title']))->all();

        Material::query()
            ->where('type', MaterialType::Bonus)
            ->whereNotIn('slug', $keepSlugs)
            ->delete();

        Material::query()
            ->whereIn('slug', [
                'guia-de-estudio-biblico-bono',
                'mapa-mental-del-pentateuco',
                'devocional-de-7-dias',
            ])
            ->delete();

        foreach ($bonuses as $bonus) {
            Material::query()->updateOrCreate(
                ['slug' => Str::slug($bonus['title'])],
                [
                    'category_id' => $category->id,
                    'plan_id' => $planBasico->id,
                    'title' => $bonus['title'],
                    'description' => $bonus['description'],
                    'cover_image' => $bonus['cover'],
                    'type' => MaterialType::Bonus,
                    'pdf_path' => $bonus['pdf'],
                    'content' => null,
                    'status' => MaterialStatus::Published,
                    'sort_order' => $bonus['order'],
                ]
            );
        }
    }
}
