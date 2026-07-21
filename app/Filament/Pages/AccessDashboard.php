<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccessStatsWidget;
use App\Filament\Widgets\LoginsChartWidget;
use App\Filament\Widgets\OnlineUsersWidget;
use App\Filament\Widgets\TopLoggersWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use UnitEnum;

class AccessDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Usuários e acesso';

    protected static ?string $navigationLabel = 'Acessos';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return 'Acessos e presença';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            AccessStatsWidget::class,
            OnlineUsersWidget::class,
            TopLoggersWidget::class,
            LoginsChartWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }
}
