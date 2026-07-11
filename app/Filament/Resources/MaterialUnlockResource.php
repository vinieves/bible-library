<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialUnlockResource\Pages;
use App\Models\Material;
use App\Models\MaterialUnlock;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MaterialUnlockResource extends Resource
{
    protected static ?string $model = MaterialUnlock::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-lock-open';

    protected static string | UnitEnum | null $navigationGroup = 'Conteúdo';

    protected static ?string $navigationLabel = 'Upsell';

    protected static ?string $modelLabel = 'liberação de upsell';

    protected static ?string $pluralModelLabel = 'liberações de upsell';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Usuário')
                    ->disabled(),
                TextInput::make('user.email')
                    ->label('E-mail')
                    ->disabled(),
                TextInput::make('material.title')
                    ->label('Material')
                    ->disabled(),
                TextInput::make('granted_by')
                    ->label('Liberado por')
                    ->disabled(),
                TextInput::make('granted_at')
                    ->label('Liberado em')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('material.title')
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase.amount')
                    ->label('Valor')
                    ->money('USD')
                    ->placeholder('—'),
                TextColumn::make('granted_by')
                    ->label('Liberado por')
                    ->badge(),
                TextColumn::make('granted_at')
                    ->label('Liberado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('granted_at', 'desc')
            ->headerActions([
                Action::make('grant')
                    ->label('Liberar acesso')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('user_id')
                            ->label('Usuário')
                            ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('material_id')
                            ->label('Material')
                            ->options(fn () => Material::query()->where('is_upsell', true)->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        MaterialUnlock::query()->updateOrCreate(
                            [
                                'user_id' => $data['user_id'],
                                'material_id' => $data['material_id'],
                            ],
                            [
                                'granted_at' => now(),
                                'granted_by' => 'admin:'.Auth::user()->email,
                                'purchase_id' => null,
                            ]
                        );

                        Notification::make()
                            ->title('Acesso liberado')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->label('Revogar')
                    ->modalHeading('Revogar acesso')
                    ->modalDescription('O usuário perderá acesso a este material imediatamente.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialUnlocks::route('/'),
            'view' => Pages\ViewMaterialUnlock::route('/{record}'),
        ];
    }
}
