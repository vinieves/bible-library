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

    public function test_selecting_a_chapter_without_a_verse_does_not_add_to_the_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/mi-biblioteca/libros/progreso', [
                'book_abbr' => 'Sal',
                'chapter' => 23,
                'verse' => null,
            ])
            ->assertOk();

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(0, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }

    public function test_clicking_a_verse_adds_one_to_the_count(): void
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

        $this->assertSame(1, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }

    public function test_clicking_the_same_verse_again_does_not_double_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(1, $progress->monthly_verses_read);
    }

    public function test_clicking_a_different_verse_in_the_same_chapter_adds_one_more(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 3,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(2, $progress->monthly_verses_read);
    }

    public function test_clicking_a_verse_in_a_new_chapter_adds_to_the_existing_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(2, $progress->monthly_verses_read);
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

        $this->assertSame(1, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }

    public function test_clicking_a_verse_for_the_first_time_starts_a_streak_of_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(1, $progress->current_streak);
        $this->assertSame(now()->toDateString(), $progress->last_activity_date->toDateString());
    }

    public function test_clicking_again_on_the_same_day_does_not_increase_the_streak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 2,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(1, $progress->current_streak);
    }

    public function test_clicking_a_verse_on_the_next_consecutive_day_increases_the_streak(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 4,
            'last_activity_date' => Carbon::yesterday(),
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(5, $progress->current_streak);
    }

    public function test_clicking_a_verse_after_skipping_a_day_resets_the_streak(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 9,
            'last_activity_date' => Carbon::now()->subDays(2),
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(1, $progress->current_streak);
    }

    public function test_selecting_a_chapter_without_a_verse_does_not_affect_the_streak(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 3,
            'last_activity_date' => Carbon::yesterday(),
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => null,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(3, $progress->current_streak);
        $this->assertSame(Carbon::yesterday()->toDateString(), $progress->last_activity_date->toDateString());
    }
}
