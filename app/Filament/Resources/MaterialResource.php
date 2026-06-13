<?php

namespace App\Filament\Resources;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static string | UnitEnum | null $navigationGroup = 'Conteúdo';

    protected static ?string $navigationLabel = 'Materiais';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiais';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações gerais')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título (visível ao cliente)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Descrição curta (visível ao cliente)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(collect(MaterialType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                            ->required(),
                        Select::make('plan_id')
                            ->label('Plano necessário')
                            ->relationship('plan', 'name', fn ($query) => $query->where('slug', 'completo'))
                            ->default(fn () => \App\Models\Plan::query()->where('slug', 'completo')->value('id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Todo conteúdo de cliente usa o Plan Completo.'),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(MaterialStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->required()
                            ->default(MaterialStatus::Draft->value),
                        TextInput::make('sort_order')
                            ->label('Ordem de exibição')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
                Section::make('Arquivos')
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Imagem de capa')
                            ->image()
                            ->disk('public')
                            ->directory('covers')
                            ->imageEditor()
                            ->columnSpanFull(),
                        FileUpload::make('pdf_path')
                            ->label('Arquivo PDF')
                            ->disk('private')
                            ->directory('pdfs')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(2097152)
                            ->helperText('Máx. ~2 GB por upload. Na VPS: PHP-FPM e Nginx com client_max_body_size 2048M.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Conteúdo interno')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Conteúdo em texto')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Capa')
                    ->disk('public'),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof MaterialType ? $state->label() : $state),
                TextColumn::make('plan.name')
                    ->label('Plano'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === MaterialStatus::Published ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof MaterialStatus ? $state->label() : $state),
                IconColumn::make('pdf_path')
                    ->label('PDF')
                    ->boolean()
                    ->getStateUsing(fn (Material $record) => filled($record->pdf_path)),
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(MaterialType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(MaterialStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
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
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
