<?php

namespace App\Filament\Widgets;

use App\Models\LoginLog;
use Filament\Widgets\ChartWidget;

class LoginsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Logins por dia (últimos 30 dias)';

    protected int|string|array $columnSpan = 'full';

    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        $start = today()->subDays(29);

        $counts = LoginLog::query()
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (LoginLog $log) => $log->created_at->format('Y-m-d'))
            ->map->count();

        $labels = [];
        $data = [];

        for ($date = $start->copy(); $date <= today(); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $data[] = $counts->get($key, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Logins',
                    'data' => $data,
                    'borderColor' => '#000000',
                    'backgroundColor' => 'rgba(0, 0, 0, 0.08)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
