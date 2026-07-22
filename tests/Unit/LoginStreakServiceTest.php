<?php

namespace Tests\Unit;

use App\Models\LoginLog;
use App\Models\User;
use App\Services\LoginStreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoginStreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private function logLoginDaysAgo(User $user, int ...$offsets): void
    {
        foreach ($offsets as $offset) {
            // Meio-dia UTC evita ambiguidade de fuso ao mapear para o dia local.
            LoginLog::create([
                'user_id' => $user->id,
                'created_at' => Carbon::now()->subDays($offset)->setTime(12, 0),
            ]);
        }
    }

    public function test_no_logins_yields_empty_streak(): void
    {
        $user = User::factory()->create();

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(0, $streak['current']);
        $this->assertSame(0, $streak['done']);
        $this->assertSame(7, $streak['remaining']);
        $this->assertFalse($streak['gift_unlocked']);
        $this->assertSame(0, $streak['line_percent']);
        $this->assertCount(7, $streak['days']);
        $this->assertFalse($streak['days'][0]['done']);
    }

    public function test_three_consecutive_days_ending_today(): void
    {
        $user = User::factory()->create();
        $this->logLoginDaysAgo($user, 0, 1, 2);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(3, $streak['current']);
        $this->assertSame(3, $streak['done']);
        $this->assertSame(4, $streak['remaining']);
        $this->assertFalse($streak['gift_unlocked']);
        $this->assertSame(33, $streak['line_percent']);
    }

    public function test_seven_days_unlocks_the_gift(): void
    {
        $user = User::factory()->create();
        $this->logLoginDaysAgo($user, 0, 1, 2, 3, 4, 5, 6);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(7, $streak['current']);
        $this->assertSame(7, $streak['done']);
        $this->assertSame(0, $streak['remaining']);
        $this->assertTrue($streak['gift_unlocked']);
        $this->assertSame(100, $streak['line_percent']);
        $this->assertTrue($streak['days'][6]['is_gift']);
        $this->assertTrue($streak['days'][6]['done']);
    }

    public function test_more_than_seven_days_caps_display_at_seven(): void
    {
        $user = User::factory()->create();
        $this->logLoginDaysAgo($user, 0, 1, 2, 3, 4, 5, 6, 7, 8);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(9, $streak['current']);
        $this->assertSame(7, $streak['done']);
        $this->assertTrue($streak['gift_unlocked']);
    }

    public function test_gap_in_the_middle_only_counts_recent_run(): void
    {
        $user = User::factory()->create();
        // Hoje e ontem, mas nada há 2 dias; login isolado há 5 dias.
        $this->logLoginDaysAgo($user, 0, 1, 5);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(2, $streak['current']);
    }

    public function test_yesterday_tolerance_keeps_streak_without_today(): void
    {
        $user = User::factory()->create();
        // Sem login hoje; ontem e anteontem.
        $this->logLoginDaysAgo($user, 1, 2);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(2, $streak['current']);
    }

    public function test_stale_streak_resets_when_last_login_is_too_old(): void
    {
        $user = User::factory()->create();
        // Último login há 3 dias (nem hoje nem ontem).
        $this->logLoginDaysAgo($user, 3, 4);

        $streak = app(LoginStreakService::class)->for($user);

        $this->assertSame(0, $streak['current']);
    }
}
