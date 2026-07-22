<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class LoginStreakService
{
    private const GOAL = 7;

    private const LOOKBACK_DAYS = 60;

    /**
     * Estrutura pronta para a view da trilha de streak de login.
     *
     * @return array{
     *     current: int,
     *     goal: int,
     *     done: int,
     *     gift_unlocked: bool,
     *     remaining: int,
     *     line_percent: int,
     *     days: list<array{index: int, done: bool, is_gift: bool}>
     * }
     */
    public function for(User $user): array
    {
        $current = $this->currentStreak($user);
        $done = min($current, self::GOAL);

        $days = [];
        for ($i = 1; $i <= self::GOAL; $i++) {
            $days[] = [
                'index' => $i,
                'done' => $i <= $done,
                'is_gift' => $i === self::GOAL,
            ];
        }

        return [
            'current' => $current,
            'goal' => self::GOAL,
            'done' => $done,
            'gift_unlocked' => $current >= self::GOAL,
            'remaining' => max(0, self::GOAL - $current),
            'line_percent' => $done > 1 ? (int) round((($done - 1) / (self::GOAL - 1)) * 100) : 0,
            'days' => $days,
        ];
    }

    /**
     * Conta dias de login consecutivos terminando em hoje (com tolerância de "ontem").
     */
    public function currentStreak(User $user): int
    {
        $tz = config('app.display_timezone', config('app.timezone', 'UTC'));

        $loggedDates = LoginLog::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn (Carbon $date): string => $date->copy()->setTimezone($tz)->toDateString())
            ->flip();

        if ($loggedDates->isEmpty()) {
            return 0;
        }

        $today = Carbon::now($tz)->startOfDay();

        // Âncora: hoje se houve login hoje; senão ontem (tolerância). Caso contrário, streak zerado.
        $cursor = $today->copy();
        if (! $loggedDates->has($cursor->toDateString())) {
            $cursor->subDay();

            if (! $loggedDates->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($loggedDates->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }
}
