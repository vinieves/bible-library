<?php

namespace Database\Seeders;

use App\Enums\AudioTrackStatus;
use App\Models\AudioCategory;
use App\Models\AudioTrack;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AudioSeeder extends Seeder
{
    public function run(): void
    {
        $planCompleto = Plan::query()->where('slug', 'completo')->firstOrFail();

        $categories = [
            ['name' => 'Introducción', 'order' => 1],
            ['name' => 'Devocionales', 'order' => 2],
            ['name' => 'Salmos', 'order' => 3],
            ['name' => 'Proverbios', 'order' => 4],
            ['name' => 'Evangelios', 'order' => 5],
            ['name' => 'Oraciones guiadas', 'order' => 6],
            ['name' => 'Estudios premium', 'order' => 7],
        ];

        foreach ($categories as $category) {
            AudioCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => 'Audios de la categoría '.$category['name'].'.',
                    'order' => $category['order'],
                    'is_active' => true,
                ]
            );
        }

        $tracks = [
            [
                'title' => 'Bienvenida a su Biblioteca en Audio',
                'description' => 'Una guía rápida para comenzar a estudiar.',
                'duration' => '03:45',
                'category' => 'introduccion',
                'cover' => 'covers/audios/bienvenida-biblioteca-audio',
                'audio' => 'audios/free/bienvenida-biblioteca-audio.mp3',
                'is_free' => true,
                'is_premium' => false,
                'order' => 1,
            ],
            [
                'title' => 'Cómo estudiar la Biblia con claridad',
                'description' => 'Consejos simples para leer con más comprensión.',
                'duration' => '06:20',
                'category' => 'introduccion',
                'cover' => 'covers/audios/como-estudiar-biblia',
                'audio' => 'audios/free/como-estudiar-biblia.mp3',
                'is_free' => true,
                'is_premium' => false,
                'order' => 2,
            ],
            [
                'title' => 'Devocional breve para comenzar el día',
                'description' => 'Una reflexión corta para fortalecer su fe.',
                'duration' => '04:10',
                'category' => 'devocionales',
                'cover' => 'covers/audios/devocional-breve',
                'audio' => 'audios/free/devocional-breve.mp3',
                'is_free' => true,
                'is_premium' => false,
                'order' => 3,
            ],
            [
                'title' => 'Salmos explicados en audio',
                'description' => 'Reflexiones narradas para fortalecer su fe.',
                'duration' => null,
                'category' => 'salmos',
                'cover' => 'covers/audios/salmos-audio',
                'audio' => null,
                'is_free' => false,
                'is_premium' => true,
                'order' => 4,
            ],
            [
                'title' => 'Proverbios explicados en audio',
                'description' => 'Sabiduría bíblica aplicada a la vida diaria.',
                'duration' => null,
                'category' => 'proverbios',
                'cover' => 'covers/audios/proverbios-audio',
                'audio' => null,
                'is_free' => false,
                'is_premium' => true,
                'order' => 5,
            ],
            [
                'title' => 'Evangelios narrados y explicados',
                'description' => 'Estudios en audio sobre las enseñanzas de Jesús.',
                'duration' => null,
                'category' => 'evangelios',
                'cover' => 'covers/audios/evangelios-audio',
                'audio' => null,
                'is_free' => false,
                'is_premium' => true,
                'order' => 6,
            ],
            [
                'title' => 'Oraciones guiadas para cada día',
                'description' => 'Momentos de oración para escuchar y acompañar su día.',
                'duration' => null,
                'category' => 'oraciones-guiadas',
                'cover' => 'covers/audios/oraciones-guiadas',
                'audio' => null,
                'is_free' => false,
                'is_premium' => true,
                'order' => 7,
            ],
            [
                'title' => 'Devocionales mensuales en audio',
                'description' => 'Nuevas reflexiones para profundizar su conexión con Dios.',
                'duration' => null,
                'category' => 'estudios-premium',
                'cover' => 'covers/audios/devocionales-mensuales',
                'audio' => null,
                'is_free' => false,
                'is_premium' => true,
                'order' => 8,
            ],
        ];

        $keepSlugs = collect($tracks)->map(fn ($track) => Str::slug($track['title']))->all();

        AudioTrack::query()
            ->whereNotIn('slug', $keepSlugs)
            ->delete();

        foreach ($tracks as $track) {
            $category = AudioCategory::query()->where('slug', $track['category'])->firstOrFail();

            AudioTrack::query()->updateOrCreate(
                ['slug' => Str::slug($track['title'])],
                [
                    'audio_category_id' => $category->id,
                    'title' => $track['title'],
                    'description' => $track['description'],
                    'cover_image' => $track['cover'],
                    'audio_file' => $track['audio'],
                    'duration' => $track['duration'],
                    'is_free' => $track['is_free'],
                    'is_premium' => $track['is_premium'],
                    'required_plan_id' => $track['is_premium'] ? $planCompleto->id : null,
                    'external_checkout_url' => null,
                    'order' => $track['order'],
                    'status' => AudioTrackStatus::Published,
                ]
            );
        }
    }
}
