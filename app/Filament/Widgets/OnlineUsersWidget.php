<?php

namespace App\Filament\Widgets;

use App\Models\Session;
use App\Support\DateTimeFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OnlineUsersWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public static function isDiscovered(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Online agora')
            ->query(
                Session::query()
                    ->online()
                    ->with('user')
                    ->orderByDesc('last_activity'),
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—'),
                TextColumn::make('user_agent')
                    ->label('Dispositivo')
                    ->formatStateUsing(fn (?string $state) => static::deviceLabel($state))
                    ->placeholder('—'),
                TextColumn::make('last_activity')
                    ->label('Ativo em')
                    ->formatStateUsing(fn ($state) => DateTimeFormat::display(
                        Carbon::createFromTimestamp((int) $state),
                        'd/m/Y H:i',
                    )),
            ])
            ->poll('60s')
            ->paginated([10, 25]);
    }

    protected static function deviceLabel(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return '—';
        }

        $isMobile = Str::contains($userAgent, ['Mobile', 'Android', 'iPhone', 'iPad'], ignoreCase: true);

        return $isMobile ? 'Celular' : 'Computador';
    }
}
