<?php

namespace App\Filament\Widgets;

use App\Models\LoginLog;
use App\Support\DateTimeFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class TopLoggersWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function isDiscovered(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Quem mais loga (30 dias)')
            ->query(
                LoginLog::query()
                    ->selectRaw('MIN(id) as id, user_id, COUNT(*) as total, MAX(created_at) as last_login')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->with('user'),
            )
            ->defaultSort('total', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->placeholder('—'),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->placeholder('—'),
                TextColumn::make('total')
                    ->label('Logins')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                TextColumn::make('last_login')
                    ->label('Último acesso')
                    ->formatStateUsing(fn ($state) => DateTimeFormat::display(
                        $state instanceof \DateTimeInterface ? $state : Carbon::parse($state),
                        'd/m/Y H:i',
                    )),
            ])
            ->paginated([10, 25]);
    }
}
