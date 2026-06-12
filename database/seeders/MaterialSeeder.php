<?php

namespace Database\Seeders;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Category;
use App\Models\Material;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $planBasico = Plan::query()->where('slug', 'basico')->firstOrFail();
        $planPremium = Plan::query()->where('slug', 'premium')->firstOrFail();

        $materials = [
            ['title' => 'Estudio Premium: Profecías', 'category' => 'estudios-premium', 'plan' => $planPremium, 'type' => MaterialType::EstudioPremium, 'order' => 1],
        ];

        foreach ($materials as $item) {
            $category = Category::query()->where('slug', $item['category'])->firstOrFail();
            $slug = Str::slug($item['title']);

            Material::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'plan_id' => $item['plan']->id,
                    'title' => $item['title'],
                    'description' => 'Material bíblico explicado versículo por versículo. Ideal para estudio diario y profundo.',
                    'cover_image' => null,
                    'type' => $item['type'],
                    'pdf_path' => null,
                    'content' => $this->sampleContent($item['title']),
                    'status' => MaterialStatus::Published,
                    'sort_order' => $item['order'],
                ]
            );
        }
    }

    private function sampleContent(string $title): string
    {
        return <<<HTML
<h2>Bienvenido a {$title}</h2>
<p>Este material forma parte de la Biblioteca Bíblica Digital. Aquí encontrará explicaciones claras, versículo por versículo, pensadas para facilitar su estudio personal o en grupo.</p>
<p>Cada sección está organizada de forma sencilla para que pueda avanzar a su propio ritmo.</p>
HTML;
    }
}
