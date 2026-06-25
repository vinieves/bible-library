<?php

namespace Tests\Feature\Members;

use App\Models\User;
use App\Models\UserBibleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_exposes_the_learning_streak_to_the_view(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 5,
            'last_activity_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/mi-biblioteca/libros');

        $response->assertOk();
        $response->assertViewHas('streak', function (array $streak) {
            return $streak['days'] === 5 && $streak['percent'] === 71;
        });
    }
}
