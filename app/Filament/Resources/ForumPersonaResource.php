<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ForumPersonaResource\Pages;
use App\Models\ForumPersona;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ForumPersonaResource extends Resource
{
    protected static ?string $model = ForumPersona::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static string | UnitEnum | null $navigationGroup = 'Comunidade';

    protected static ?string $navigationLabel = 'Personas';

    protected static ?string $modelLabel = 'persona';

    protected static ?string $pluralModelLabel = 'personas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('photo')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory('forum-personas')
                    ->imageEditor()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->label('Publicações')
                    ->counts('posts'),
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
            'index' => Pages\ListForumPersonas::route('/'),
            'create' => Pages\CreateForumPersona::route('/create'),
            'edit' => Pages\EditForumPersona::route('/{record}/edit'),
        ];
    }
}
