<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginLogResource\Pages;
use App\Models\LoginLog;
use App\Support\DateTimeFormat;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class LoginLogResource extends Resource
{
    protected static ?string $model = LoginLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static string|UnitEnum|null $navigationGroup = 'Usuários e acesso';

    protected static ?string $navigationLabel = 'Histórico de logins';

    protected static ?string $modelLabel = 'login';

    protected static ?string $pluralModelLabel = 'logins';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function deviceLabel(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return '—';
        }

        return Str::contains($userAgent, ['Mobile', 'Android', 'iPhone', 'iPad'], ignoreCase: true)
            ? 'Celular'
            : 'Computador';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Login')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Usuário')
                            ->disabled(),
                        TextInput::make('user.email')
                            ->label('E-mail')
                            ->disabled(),
                        TextInput::make('ip_address')
                            ->label('IP')
                            ->disabled(),
                        TextInput::make('user_agent')
                            ->label('Navegador / dispositivo')
                            ->disabled()
                            ->columnSpanFull(),
                        TextInput::make('created_at')
                            ->label('Data')
                            ->formatStateUsing(fn ($state) => DateTimeFormat::display(
                                $state instanceof \DateTimeInterface ? $state : null,
                            ))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->formatStateUsing(DateTimeFormat::filamentColumn('d/m/Y H:i:s'))
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('user_agent')
                    ->label('Dispositivo')
                    ->formatStateUsing(fn (?string $state) => static::deviceLabel($state))
                    ->tooltip(fn (?string $state) => $state)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('period')
                    ->label('Período')
                    ->options([
                        '7' => 'Últimos 7 dias',
                        '30' => 'Últimos 30 dias',
                        '90' => 'Últimos 90 dias',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->where('created_at', '>=', now()->subDays((int) $data['value']))
                        : $query),
                Filter::make('has_user')
                    ->label('Somente com usuário identificado')
                    ->query(fn ($query) => $query->whereNotNull('user_id')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginLogs::route('/'),
            'view' => Pages\ViewLoginLog::route('/{record}'),
        ];
    }
}
