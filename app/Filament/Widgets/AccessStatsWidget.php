<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LoginLogResource;
use App\Filament\Resources\UserResource;
use App\Models\LoginLog;
use App\Models\Session;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccessStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

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
                ->description('Usuários ativos nos últimos 5 minutos (lista abaixo)')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),
            Stat::make('Logins hoje', $loginsToday)
                ->description('Ver quem logou hoje')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('info')
                ->url(LoginLogResource::getUrl('index', [
                    'tableFilters' => ['period' => ['value' => 'today']],
                ])),
            Stat::make('Novos cadastros (7 dias)', $newUsers7d)
                ->description('Ver cadastros recentes')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->url(UserResource::getUrl('index', [
                    'tableFilters' => ['login_status' => ['value' => 'new7']],
                ])),
            Stat::make('Usuários sumidos (30 dias+)', $dormant30d)
                ->description('Ver sumidos e quem nunca logou')
                ->descriptionIcon('heroicon-m-moon')
                ->color('warning')
                ->url(UserResource::getUrl('index', [
                    'tableFilters' => ['login_status' => ['value' => 'dormant']],
                ])),
        ];
    }
}
