<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | UnitEnum | null $navigationGroup = 'Loja';

    protected static ?string $navigationLabel = 'Produtos internos';

    protected static ?string $modelLabel = 'produto';

    protected static ?string $pluralModelLabel = 'produtos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('product_code')
                    ->label('Código do produto')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Deve coincidir com o ID, ucode ou offer code do produto na Hotmart (ou código no webhook genérico).'),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Preço')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                FileUpload::make('image')
                    ->label('Imagem')
                    ->image()
                    ->disk('public')
                    ->directory('products'),
                TextInput::make('checkout_url')
                    ->label('Link externo de checkout')
                    ->url()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Toggle::make('grants_access')
                    ->label('Libera acesso ao Plan Completo')
                    ->default(true)
                    ->live()
                    ->helperText('Desative para order bumps e upsells que só devem ser registrados.'),
                Select::make('plan_id')
                    ->label('Plano que libera')
                    ->relationship('plan', 'name', fn ($query) => $query->where('slug', 'completo'))
                    ->default(fn () => \App\Models\Plan::query()->where('slug', 'completo')->value('id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => (bool) $get('grants_access'))
                    ->required(fn (Get $get): bool => (bool) $get('grants_access'))
                    ->helperText('Usado apenas quando o produto libera acesso.'),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagem')
                    ->disk('public'),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->placeholder('—'),
                IconColumn::make('grants_access')
                    ->label('Libera acesso')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
