<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\DateTimeFormat;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Usuários e acesso';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do usuário')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->maxLength(255),
                        Toggle::make('is_admin')
                            ->label('Administrador')
                            ->helperText('Permite acessar o painel /admin'),
                    ])
                    ->columns(2),
                Section::make('Planos de acesso')
                    ->description('Libere manualmente os planos que este usuário pode usar.')
                    ->schema([
                        Select::make('plans')
                            ->label('Planos atribuídos')
                            ->relationship('plans', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                TextColumn::make('plans.name')
                    ->label('Planos')
                    ->badge()
                    ->limitList(3),
                TextColumn::make('last_login_at')
                    ->label('Último acesso')
                    ->formatStateUsing(fn ($state) => $state
                        ? DateTimeFormat::display($state instanceof \DateTimeInterface ? $state : null, 'd/m/Y H:i')
                        : 'Nunca')
                    ->badge(fn ($state) => blank($state))
                    ->color(fn ($state) => blank($state) ? 'warning' : null)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('Administrador'),
                SelectFilter::make('login_status')
                    ->label('Situação de login')
                    ->options([
                        'dormant' => 'Sumidos (30 dias+ ou nunca)',
                        'never' => 'Nunca logaram',
                        'active7' => 'Ativos (últimos 7 dias)',
                        'new7' => 'Cadastrados nos últimos 7 dias',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'dormant' => $query->where('is_admin', false)
                                ->where(fn ($q) => $q->whereNull('last_login_at')
                                    ->orWhere('last_login_at', '<', now()->subDays(30))),
                            'never' => $query->where('is_admin', false)
                                ->whereNull('last_login_at'),
                            'active7' => $query->where('last_login_at', '>=', now()->subDays(7)),
                            'new7' => $query->where('created_at', '>=', now()->subDays(7)),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
