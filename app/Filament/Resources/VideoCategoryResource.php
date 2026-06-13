<?php

namespace App\Filament\Resources;

use App\Enums\CategoryBadgeColor;
use App\Filament\Resources\VideoCategoryResource\Pages;
use App\Models\VideoCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VideoCategoryResource extends Resource
{
    protected static ?string $model = VideoCategory::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | UnitEnum | null $navigationGroup = 'Vídeo';

    protected static ?string $navigationLabel = 'Categorias de vídeo';

    protected static ?string $modelLabel = 'categoria de vídeo';

    protected static ?string $pluralModelLabel = 'categorias de vídeo';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informações')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome (visível ao cliente)')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('badge_color')
                        ->label('Cor da badge (cliente)')
                        ->options(CategoryBadgeColor::options())
                        ->default(CategoryBadgeColor::Gold->value)
                        ->required(),
                    Textarea::make('description')
                        ->label('Descrição (visível ao cliente)')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('order')
                        ->label('Ordem')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Ativa')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('badge_color')
                    ->label('Badge')
                    ->formatStateUsing(fn (?string $state) => CategoryBadgeColor::tryFrom((string) $state)?->label() ?? 'Dourado'),
                TextColumn::make('videos_count')
                    ->label('Vídeos')
                    ->counts('videos'),
                IconColumn::make('is_active')->label('Ativa')->boolean(),
                TextColumn::make('order')->label('Ordem')->sortable(),
            ])
            ->defaultSort('order')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideoCategories::route('/'),
            'create' => Pages\CreateVideoCategory::route('/create'),
            'edit' => Pages\EditVideoCategory::route('/{record}/edit'),
        ];
    }
}
