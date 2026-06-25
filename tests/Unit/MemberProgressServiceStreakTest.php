<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserBibleProgress;
use App\Services\MemberProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProgressServiceStreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_streak_defaults_to_zero_with_no_progress_row(): void
    {
        $user = User::factory()->create();

        $streak = app(MemberProgressService::class)->learningStreak($user);

        $this->assertSame(0, $streak['days']);
        $this->assertSame(0, $streak['percent']);
    }

    public function test_learning_streak_reflects_current_streak_and_percent_of_weekly_goal(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 3,
            'last_activity_date' => now(),
        ]);

        $streak = app(MemberProgressService::class)->learningStreak($user);

        $this->assertSame(3, $streak['days']);
        $this->assertSame(43, $streak['percent']);
    }

    public function test_learning_streak_caps_percent_at_one_hundred_past_the_weekly_goal(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'current_streak' => 21,
            'last_activity_date' => now(),
        ]);

        $streak = app(MemberProgressService::class)->learningStreak($user);

        $this->assertSame(21, $streak['days']);
        $this->assertSame(100, $streak['percent']);
    }
}
