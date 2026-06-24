<?php

namespace App\Filament\Resources;

use App\Enums\WhatsAppDispatchStatus;
use App\Enums\WhatsAppDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Filament\Resources\WhatsAppDispatchLogResource\Pages;
use App\Models\WhatsAppDispatchLog;
use App\Support\DateTimeFormat;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppDispatchLogResource extends Resource
{
    protected static ?string $model = WhatsAppDispatchLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Disparos WhatsApp';

    protected static ?string $modelLabel = 'disparo WhatsApp';

    protected static ?string $pluralModelLabel = 'disparos WhatsApp';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->schema([
                        TextInput::make('message_event')
                            ->label('Tipo de disparo')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppMessageEvent
                                ? $state->conditionLabel()
                                : WhatsAppMessageEvent::tryFrom((string) $state)?->conditionLabel() ?? $state)
                            ->disabled(),
                        TextInput::make('instance_name')
                            ->label('Instância WhatsApp')
                            ->disabled(),
                        TextInput::make('trigger')
                            ->label('Canal')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchTrigger
                                ? $state->label()
                                : WhatsAppDispatchTrigger::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Resultado')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchStatus
                                ? $state->label()
                                : WhatsAppDispatchStatus::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Telefone informado')
                            ->disabled(),
                        TextInput::make('phone_normalized')
                            ->label('Telefone normalizado')
                            ->disabled(),
                        TextInput::make('user_email')
                            ->label('Usuário')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('purchase_id')
                            ->label('ID da compra')
                            ->disabled(),
                        TextInput::make('attempt')
                            ->label('Tentativa')
                            ->disabled(),
                        TextInput::make('http_status')
                            ->label('HTTP')
                            ->disabled(),
                        TextInput::make('created_at')
                            ->label('Enviado em')
                            ->formatStateUsing(fn ($state) => DateTimeFormat::display($state instanceof \DateTimeInterface ? $state : null))
                            ->disabled(),
                        Textarea::make('error_message')
                            ->label('Erro')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Mensagem')
                    ->schema([
                        Textarea::make('message')
                            ->label('Texto enviado')
                            ->rows(10)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                Section::make('Resposta Evolution API')
                    ->schema([
                        Textarea::make('evolution_response')
                            ->label('JSON')
                            ->formatStateUsing(fn ($state) => filled($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : '—')
                            ->rows(12)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('message_event')
                    ->label('Origem')
                    ->badge()
                    ->color(fn (WhatsAppDispatchLog $record) => WhatsAppMessageEvent::resolveOriginColor(
                        $record->message_event?->value,
                        $record->trigger,
                    ))
                    ->formatStateUsing(fn (WhatsAppDispatchLog $record) => WhatsAppMessageEvent::resolveOrigin(
                        $record->message_event?->value,
                        $record->trigger,
                    ))
                    ->sortable(),
                TextColumn::make('instance_name')
                    ->label('Instância')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn ($state) => $state instanceof WhatsAppDispatchStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchStatus ? $state->label() : $state),
                TextColumn::make('phone_normalized')
                    ->label('Telefone')
                    ->searchable(['phone', 'phone_normalized'])
                    ->placeholder('—'),
                TextColumn::make('user.email')
                    ->label('Usuário')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('attempt')
                    ->label('Tent.')
                    ->alignCenter(),
                TextColumn::make('error_message')
                    ->label('Erro')
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('http_status')
                    ->label('HTTP')
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('message_event')
                    ->label('Origem')
                    ->options(collect(WhatsAppMessageEvent::cases())->mapWithKeys(
                        fn (WhatsAppMessageEvent $event) => [$event->value => $event->originLabel()],
                    )),
                SelectFilter::make('status')
                    ->label('Resultado')
                    ->options(collect(WhatsAppDispatchStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppDispatchLogs::route('/'),
            'view' => Pages\ViewWhatsAppDispatchLog::route('/{record}'),
        ];
    }
}
