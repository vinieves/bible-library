<?php

namespace App\Filament\Resources;

use App\Enums\VideoStatus;
use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
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

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static string | UnitEnum | null $navigationGroup = 'Vídeo';

    protected static ?string $navigationLabel = 'Vídeos';

    protected static ?string $modelLabel = 'vídeo';

    protected static ?string $pluralModelLabel = 'vídeos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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
                        ->label('Descrição (visível ao cliente)')
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('video_category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('duration')
                        ->label('Duração (mm:ss ou hh:mm:ss)')
                        ->placeholder('12:45'),
                    TextInput::make('order')
                        ->label('Ordem')
                        ->numeric()
                        ->default(0),
                    Select::make('status')
                        ->label('Status')
                        ->options(collect(VideoStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                        ->required()
                        ->default(VideoStatus::Draft->value),
                ])
                ->columns(2),
            Section::make('Acesso e monetização')
                ->schema([
                    Toggle::make('is_free')
                        ->label('Gratuito')
                        ->default(false),
                    Toggle::make('is_premium')
                        ->label('Premium')
                        ->default(true),
                    Select::make('required_plan_id')
                        ->label('Plano necessário')
                        ->relationship('requiredPlan', 'name', fn ($query) => $query->where('slug', 'completo'))
                        ->default(fn () => \App\Models\Plan::query()->where('slug', 'completo')->value('id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Vídeos premium exigem o Plan Completo.'),
                    TextInput::make('external_checkout_url')
                        ->label('URL de checkout externo (opcional)')
                        ->url()
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Arquivos')
                ->schema([
                    FileUpload::make('cover_image')
                        ->label('Imagem de capa')
                        ->image()
                        ->disk('public')
                        ->directory('covers/videos')
                        ->imageEditor()
                        ->columnSpanFull(),
                    FileUpload::make('video_file')
                        ->label('Arquivo de vídeo (MP4)')
                        ->disk('private')
                        ->directory('videos')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                        ->maxSize(512000)
                        ->helperText('MP4 recomendado. Máx. ~500 MB por upload (ajuste PHP/Nginx na VPS para arquivos maiores).')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')->label('Capa')->disk('public'),
                TextColumn::make('title')->label('Título')->searchable()->sortable()->limit(40),
                TextColumn::make('category.name')->label('Categoria'),
                IconColumn::make('is_free')->label('Grátis')->boolean(),
                IconColumn::make('is_premium')->label('Premium')->boolean(),
                TextColumn::make('duration')->label('Duração'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === VideoStatus::Published ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof VideoStatus ? $state->label() : $state),
                TextColumn::make('order')->label('Ordem')->sortable(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                SelectFilter::make('video_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(VideoStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
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
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
