<?php

namespace Tests\Feature\Members;

use App\Models\User;
use App\Models\UserBibleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LibraryControllerProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_progress_on_a_new_chapter_adds_its_verse_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/mi-biblioteca/libros/progreso', [
                'book_abbr' => 'Sal',
                'chapter' => 23,
                'verse' => 1,
            ])
            ->assertOk();

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }

    public function test_saving_progress_again_on_the_same_chapter_does_not_double_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 3,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6, $progress->monthly_verses_read);
    }

    public function test_advancing_to_a_new_chapter_adds_to_the_existing_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6 + 34, $progress->monthly_verses_read);
    }

    public function test_counter_resets_when_the_stored_period_is_a_previous_month(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'verse' => 1,
            'monthly_verses_read' => 27,
            'monthly_period' => Carbon::now()->subMonth()->format('Y-m'),
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(34, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }
}
