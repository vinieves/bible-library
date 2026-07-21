<?php

namespace App\Filament\Resources;

use App\Enums\ForumPostStatus;
use App\Filament\Resources\ForumPostResource\Pages;
use App\Models\ForumPost;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ForumPostResource extends Resource
{
    protected static ?string $model = ForumPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Comunidade';

    protected static ?string $navigationLabel = 'Publicações';

    protected static ?string $modelLabel = 'publicação';

    protected static ?string $pluralModelLabel = 'publicações';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('forum_persona_id')
                    ->label('Persona/Autor')
                    ->relationship('persona', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
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
                    ]),
                Section::make('Informações gerais')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Imagens (carrossel)')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('forum-galleries')
                            ->columnSpanFull(),
                        TextInput::make('reactions_boost')
                            ->label('Quantidade de likes (🙏 Amém)')
                            ->helperText('Número inicial exibido. Reações reais dos membros somam a partir daqui.')
                            ->numeric()
                            ->minValue(0)
                            ->default(20)
                            ->required(),
                        TextInput::make('youtube_url')
                            ->label('Link do YouTube (opcional)')
                            ->url()
                            ->helperText('Cole o link completo do YouTube, ex: https://www.youtube.com/watch?v=...')
                            ->columnSpanFull(),
                        FileUpload::make('audio_file')
                            ->label('Arquivo de áudio (opcional)')
                            ->disk('private')
                            ->directory('forum-audio')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'])
                            ->maxSize(102400)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(ForumPostStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->required()
                            ->default(ForumPostStatus::Draft->value),
                    ])
                    ->columns(2),
                Section::make('Conteúdo da publicação')
                    ->schema([
                        RichEditor::make('body')
                            ->label('Texto')
                            ->helperText('Os membros poderão reagir com 🙏 Amém a esta publicação.')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('persona.photo')
                    ->label('Foto')
                    ->circular()
                    ->disk('public'),
                TextColumn::make('persona.name')
                    ->label('Persona')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('images')
                    ->label('Imagens')
                    ->disk('public')
                    ->stacked()
                    ->limit(3),
                TextColumn::make('body')
                    ->label('Conteúdo')
                    ->getStateUsing(fn (ForumPost $record) => Str::limit(trim(strip_tags($record->body)), 80))
                    ->wrap(),
                IconColumn::make('youtube_url')
                    ->label('Vídeo')
                    ->boolean()
                    ->getStateUsing(fn (ForumPost $record) => filled($record->youtube_url)),
                IconColumn::make('audio_file')
                    ->label('Áudio')
                    ->boolean()
                    ->getStateUsing(fn (ForumPost $record) => filled($record->audio_file)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === ForumPostStatus::Published ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof ForumPostStatus ? $state->label() : $state),
                TextColumn::make('reactions_count')
                    ->label('🙏 Amém')
                    ->counts('reactions')
                    ->getStateUsing(fn (ForumPost $record) => $record->totalReactionsCount()),
                TextColumn::make('created_at')
                    ->label('Publicado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ForumPostStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                Action::make('editContent')
                    ->label('Editar texto')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar conteúdo da publicação')
                    ->form([
                        RichEditor::make('body')
                            ->label('Texto')
                            ->required(),
                    ])
                    ->fillForm(fn (ForumPost $record) => ['body' => $record->body])
                    ->action(function (ForumPost $record, array $data) {
                        $record->update(['body' => $data['body']]);
                    }),
                Action::make('editImages')
                    ->label('Editar imagens/likes')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Editar imagens e quantidade de likes')
                    ->form([
                        FileUpload::make('images')
                            ->label('Imagens (carrossel)')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('forum-galleries'),
                        TextInput::make('reactions_boost')
                            ->label('Quantidade de likes (🙏 Amém)')
                            ->helperText('Número inicial exibido. Reações reais dos membros somam a partir daqui.')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->fillForm(fn (ForumPost $record) => [
                        'images' => $record->images,
                        'reactions_boost' => $record->reactions_boost,
                    ])
                    ->action(function (ForumPost $record, array $data) {
                        $record->update([
                            'images' => $data['images'] ?? [],
                            'reactions_boost' => $data['reactions_boost'],
                        ]);
                    }),
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
            'index' => Pages\ListForumPosts::route('/'),
            'create' => Pages\CreateForumPost::route('/create'),
            'edit' => Pages\EditForumPost::route('/{record}/edit'),
        ];
    }
}
