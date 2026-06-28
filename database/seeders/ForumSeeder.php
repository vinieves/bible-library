<?php

namespace Database\Seeders;

use App\Enums\ForumPostStatus;
use App\Models\ForumPersona;
use App\Models\ForumPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $personas = $this->seedPersonas();

        $this->seedPosts($personas);
    }

    /**
     * @return array<string, ForumPersona>
     */
    private function seedPersonas(): array
    {
        $names = [
            'María González',
            'Carlos Rodríguez',
            'Ana Martínez',
            'Luis Fernández',
            'Sofía Pérez',
            'Diego López',
            'Valentina Sánchez',
            'Andrés Ramírez',
            'Camila Torres',
            'Javier Flores',
        ];

        $personas = [];

        foreach ($names as $name) {
            $personas[$name] = ForumPersona::query()->firstOrCreate(
                ['name' => $name],
                ['photo' => null],
            );
        }

        return $personas;
    }

    /**
     * @param  array<string, ForumPersona>  $personas
     */
    private function seedPosts(array $personas): void
    {
        $now = Carbon::now();

        $posts = [
            [
                'persona' => 'María González',
                'title' => 'Una nueva forma de entender la Palabra',
                'body' => '<p>Llevo una semana con los estudios y por fin entiendo pasajes que antes leía sin comprender. 🙏 Las explicaciones versículo por versículo cambian todo. ¡Gracias por este material tan bonito!</p>',
                'days_ago' => 14,
            ],
            [
                'persona' => 'Carlos Rodríguez',
                'title' => null,
                'body' => '<p>«El temor del Señor es el principio de la sabiduría.» — Proverbios 9:10 📖✨</p>',
                'days_ago' => 13,
            ],
            [
                'persona' => 'Ana Martínez',
                'title' => 'El material me está ayudando muchísimo',
                'body' => '<p>Cada mañana dedico 15 minutos al estudio del día y mi paz interior es otra. 😊 Sentir que entiendo lo que leo me acercó muchísimo más a Dios.</p>',
                'days_ago' => 12,
            ],
            [
                'persona' => 'Luis Fernández',
                'title' => null,
                'body' => '<p>Hoy comparto este versículo que me sostuvo toda la semana:</p><p>«Todo lo puedo en Cristo que me fortalece.» — Filipenses 4:13 💪🙏</p>',
                'days_ago' => 11,
            ],
            [
                'persona' => 'Sofía Pérez',
                'title' => 'Las explicaciones son una joya',
                'body' => '<p>Me encanta que cada capítulo venga explicado con contexto histórico. Antes me perdía con los nombres y lugares, ahora todo tiene sentido. 🤍</p>',
                'days_ago' => 10,
            ],
            [
                'persona' => 'Diego López',
                'title' => null,
                'body' => '<p>«Mira que te mando que te esfuerces y seas valiente; no temas ni desmayes, porque Jehová tu Dios estará contigo dondequiera que vayas.» — Josué 1:9 🕊️</p>',
                'days_ago' => 9,
            ],
            [
                'persona' => 'Valentina Sánchez',
                'title' => 'Estudiar en familia',
                'body' => '<p>Empecé a leer las explicaciones con mis hijos antes de dormir y se volvió nuestro momento favorito del día. 👨‍👩‍👧‍👦📖 Gracias por hacer la Biblia tan accesible.</p>',
                'days_ago' => 8,
            ],
            [
                'persona' => 'Andrés Ramírez',
                'title' => null,
                'body' => '<p>Reflexión de hoy: a veces no entendemos el camino, pero Él sí.</p><p>«Confía en Jehová con todo tu corazón, y no te apoyes en tu propia prudencia.» — Proverbios 3:5 🙌</p>',
                'days_ago' => 7,
            ],
            [
                'persona' => 'Camila Torres',
                'title' => 'Del audio aprendo mientras trabajo',
                'body' => '<p>Escuchar las explicaciones mientras hago las tareas de casa fue lo mejor que descubrí. 🎧 Aprovecho el tiempo y alimento mi fe al mismo tiempo.</p>',
                'days_ago' => 6,
            ],
            [
                'persona' => 'Javier Flores',
                'title' => null,
                'body' => '<p>«Lámpara es a mis pies tu palabra, y lumbrera a mi camino.» — Salmos 119:105 🔥</p>',
                'days_ago' => 5,
            ],
            [
                'persona' => 'María González',
                'title' => 'Por fin entiendo el Apocalipsis',
                'body' => '<p>Era el libro que más miedo me daba leer 😅, pero con las explicaciones todo se aclara. Recomiendo muchísimo ir capítulo por capítulo sin prisa.</p>',
                'days_ago' => 4,
            ],
            [
                'persona' => 'Ana Martínez',
                'title' => null,
                'body' => '<p>Para empezar bien el día:</p><p>«Este es el día que hizo Jehová; nos gozaremos y alegraremos en él.» — Salmos 118:24 ☀️🙏</p>',
                'days_ago' => 3,
            ],
            [
                'persona' => 'Diego López',
                'title' => 'Gracias por el contenido',
                'body' => '<p>Nunca había logrado leer la Biblia de forma constante. Con este material ya llevo 21 días seguidos. 📈 La diferencia está en entender lo que leo.</p>',
                'days_ago' => 2,
            ],
            [
                'persona' => 'Sofía Pérez',
                'title' => null,
                'body' => '<p>«Jehová es mi pastor; nada me faltará.» — Salmos 23:1 🕊️🤍 Que tengan un día lleno de paz.</p>',
                'days_ago' => 1,
            ],
            [
                'persona' => 'Valentina Sánchez',
                'title' => 'Mi parte favorita',
                'body' => '<p>Lo que más me ayuda son las reflexiones al final de cada estudio. Me hacen pensar cómo aplicar la enseñanza en mi vida diaria. ❤️ ¡Bendiciones para toda la comunidad!</p>',
                'days_ago' => 0,
            ],
        ];

        foreach ($posts as $data) {
            $persona = $personas[$data['persona']];

            $createdAt = $now->copy()
                ->subDays($data['days_ago'])
                ->setTime(rand(7, 21), rand(0, 59));

            ForumPost::query()->updateOrCreate(
                [
                    'forum_persona_id' => $persona->id,
                    'body' => $data['body'],
                ],
                [
                    'title' => $data['title'],
                    'images' => null,
                    'youtube_url' => null,
                    'audio_file' => null,
                    'status' => ForumPostStatus::Published->value,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );
        }
    }
}
