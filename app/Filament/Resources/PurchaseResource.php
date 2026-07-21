<?php

namespace App\Filament\Resources;

use App\Enums\PurchaseStatus;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Loja';

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $modelLabel = 'compra';

    protected static ?string $pluralModelLabel = 'compras';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('E-mail')
                    ->disabled(),
                TextInput::make('name')
                    ->label('Nome')
                    ->disabled(),
                TextInput::make('phone')
                    ->label('Telefone')
                    ->disabled(),
                TextInput::make('platform')
                    ->label('Plataforma')
                    ->disabled(),
                TextInput::make('external_reference')
                    ->label('Referência externa')
                    ->disabled(),
                TextInput::make('product_code')
                    ->label('Código do produto')
                    ->disabled(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(PurchaseStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                    ->disabled(),
                TextInput::make('amount')
                    ->label('Valor')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('Plataforma')
                    ->badge(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('external_reference')
                    ->label('Referência')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan.name')
                    ->label('Plano'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PurchaseStatus::Approved => 'success',
                        PurchaseStatus::Pending => 'warning',
                        PurchaseStatus::Rejected => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof PurchaseStatus ? $state->label() : $state),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('USD'),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(PurchaseStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'view' => Pages\ViewPurchase::route('/{record}'),
        ];
    }
}
