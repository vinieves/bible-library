<?php

namespace App\Filament\Widgets;

use App\Models\LoginLog;
use App\Models\Session;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccessStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $onlineNow = Session::query()->online()->distinct()->count('user_id');

        $loginsToday = LoginLog::query()
            ->whereDate('created_at', today())
            ->count();

        $newUsers7d = User::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $dormant30d = User::query()
            ->where('is_admin', false)
            ->where(function ($query) {
                $query->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', now()->subDays(30));
            })
            ->count();

        return [
            Stat::make('Online agora', $onlineNow)
                ->description('Usuários ativos nos últimos 5 minutos')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),
            Stat::make('Logins hoje', $loginsToday)
                ->description('Total de acessos de hoje')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('info'),
            Stat::make('Novos cadastros (7 dias)', $newUsers7d)
                ->description('Usuários registrados na última semana')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
            Stat::make('Usuários sumidos (30 dias+)', $dormant30d)
                ->description('Sem login há mais de 30 dias')
                ->descriptionIcon('heroicon-m-moon')
                ->color('warning'),
        ];
    }
}
