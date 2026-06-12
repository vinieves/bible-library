<?php

namespace App\Filament\Resources;

use App\Enums\AudioTrackStatus;
use App\Filament\Resources\AudioTrackResource\Pages;
use App\Models\AudioTrack;
use BackedEnum;
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

class AudioTrackResource extends Resource
{
    protected static ?string $model = AudioTrack::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-speaker-wave';

    protected static string | UnitEnum | null $navigationGroup = 'Áudio';

    protected static ?string $navigationLabel = 'Áudios';

    protected static ?string $modelLabel = 'áudio';

    protected static ?string $pluralModelLabel = 'áudios';

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
                    Select::make('audio_category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('duration')
                        ->label('Duração (mm:ss)')
                        ->placeholder('03:45'),
                    TextInput::make('order')
                        ->label('Ordem')
                        ->numeric()
                        ->default(0),
                    Select::make('status')
                        ->label('Status')
                        ->options(collect(AudioTrackStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                        ->required()
                        ->default(AudioTrackStatus::Draft->value),
                ])
                ->columns(2),
            Section::make('Acesso e monetização')
                ->schema([
                    Toggle::make('is_free')
                        ->label('Gratuito')
                        ->default(false),
                    Toggle::make('is_premium')
                        ->label('Premium')
                        ->default(false),
                    Select::make('required_plan_id')
                        ->label('Plano necessário')
                        ->relationship('requiredPlan', 'name')
                        ->searchable()
                        ->preload(),
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
                        ->directory('covers/audios')
                        ->columnSpanFull(),
                    FileUpload::make('audio_file')
                        ->label('Arquivo de áudio')
                        ->disk('private')
                        ->directory('audios')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'])
                        ->maxSize(102400)
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
                    ->color(fn ($state) => $state === AudioTrackStatus::Published ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof AudioTrackStatus ? $state->label() : $state),
                TextColumn::make('order')->label('Ordem')->sortable(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                SelectFilter::make('audio_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(AudioTrackStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudioTracks::route('/'),
            'create' => Pages\CreateAudioTrack::route('/create'),
            'edit' => Pages\EditAudioTrack::route('/{record}/edit'),
        ];
    }
}
