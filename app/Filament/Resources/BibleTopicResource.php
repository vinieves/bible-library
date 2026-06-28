<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleTopicResource\Pages;
use App\Models\BibleTopic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BibleTopicResource extends Resource
{
    protected static ?string $model = BibleTopic::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | UnitEnum | null $navigationGroup = 'Conteúdo';

    protected static ?string $navigationLabel = 'Tópicos bíblicos';

    protected static ?string $modelLabel = 'tópico';

    protected static ?string $pluralModelLabel = 'tópicos';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Repeater::make('verses')
                    ->label('Versículos')
                    ->relationship('verses')
                    ->orderColumn('sort_order')
                    ->reorderableWithDragAndDrop()
                    ->addActionLabel('+ Adicionar versículo')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('book_abbr')
                            ->label('Livro')
                            ->options(config('bible.books'))
                            ->searchable()
                            ->required(),
                        TextInput::make('chapter')
                            ->label('Capítulo')
                            ->numeric()
                            ->required(),
                        TextInput::make('verse')
                            ->label('Versículo')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('verses_count')
                    ->label('Versículos')
                    ->counts('verses'),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index' => Pages\ListBibleTopics::route('/'),
            'create' => Pages\CreateBibleTopic::route('/create'),
            'edit' => Pages\EditBibleTopic::route('/{record}/edit'),
        ];
    }
}
