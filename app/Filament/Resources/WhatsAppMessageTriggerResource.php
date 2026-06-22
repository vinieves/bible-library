<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppMessageTriggerResource\Pages;
use App\Models\WhatsAppMessageTrigger;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
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

class WhatsAppMessageTriggerResource extends Resource
{
    protected static ?string $model = WhatsAppMessageTrigger::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bolt';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Gatilhos';

    protected static ?string $modelLabel = 'gatilho';

    protected static ?string $pluralModelLabel = 'gatilhos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gatilho de mensagem')
                    ->description('Use o texto exato que o cliente envia ao clicar no anúncio (WhatsApp). O sistema compara com data.message.conversation do webhook Evolution.')
                    ->schema([
                        TextInput::make('public_code')
                            ->label('ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?WhatsAppMessageTrigger $record): bool => $record !== null)
                            ->placeholder('Gerado automaticamente após salvar'),
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nome interno para identificar o anúncio ou campanha.'),
                        Textarea::make('message')
                            ->label('Mensagem')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Cole o texto exato da primeira mensagem, incluindo emojis. Ex.: "👋 Hola, quiero saber más" ou "Oi quero ebook".'),
                        Toggle::make('is_active')
                            ->label('Gatilho ativo')
                            ->default(true)
                            ->helperText('Somente gatilhos ativos participam da correspondência com webhooks.'),
                        Placeholder::make('flows_hint')
                            ->label('Uso em fluxos')
                            ->content('Depois de criar, edite um fluxo em Sistema → Fluxos, escolha o gatilho "Gatilho por mensagem" e selecione este gatilho.')
                            ->visible(fn (?WhatsAppMessageTrigger $record): bool => $record !== null)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_code')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Mensagem')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state)
                    ->searchable(),
                TextColumn::make('flows_count')
                    ->label('Fluxos')
                    ->counts('flows')
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('public_code')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppMessageTriggers::route('/'),
            'create' => Pages\CreateWhatsAppMessageTrigger::route('/create'),
            'edit' => Pages\EditWhatsAppMessageTrigger::route('/{record}/edit'),
        ];
    }
}
