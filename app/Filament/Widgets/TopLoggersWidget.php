<?php

namespace App\Filament\Widgets;

use App\Models\User;
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
                // Base na tabela users e agrega por users.id (chave primária):
                // válido no MySQL only_full_group_by e o desempate do Filament
                // (order by users.id) fica dentro do GROUP BY.
                User::query()
                    ->join('login_logs', 'login_logs.user_id', '=', 'users.id')
                    ->where('login_logs.created_at', '>=', now()->subDays(30))
                    ->groupBy('users.id')
                    ->select('users.*')
                    ->selectRaw('COUNT(login_logs.id) as total')
                    ->selectRaw('MAX(login_logs.created_at) as last_login'),
            )
            ->defaultSort('total', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Usuário')
                    ->placeholder('—'),
                TextColumn::make('email')
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
