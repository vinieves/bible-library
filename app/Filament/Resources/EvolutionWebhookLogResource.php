<?php

namespace App\Filament\Resources;

use App\Enums\EvolutionWebhookLogStatus;
use App\Filament\Resources\EvolutionWebhookLogResource\Pages;
use App\Models\EvolutionWebhookLog;
use App\Support\DateTimeFormat;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EvolutionWebhookLogResource extends Resource
{
    protected static ?string $model = EvolutionWebhookLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Webhooks Evolution';

    protected static ?string $modelLabel = 'webhook Evolution';

    protected static ?string $pluralModelLabel = 'webhooks Evolution';

    protected static ?int $navigationSort = 6;

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
                        TextInput::make('event')
                            ->label('Evento')
                            ->disabled(),
                        TextInput::make('instance')
                            ->label('Instância')
                            ->disabled(),
                        TextInput::make('route_slug')
                            ->label('Rota (slug)')
                            ->disabled(),
                        TextInput::make('processing_status')
                            ->label('Resultado')
                            ->formatStateUsing(fn ($state) => $state instanceof EvolutionWebhookLogStatus
                                ? $state->label()
                                : EvolutionWebhookLogStatus::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('inbound_count')
                            ->label('Mensagens válidas')
                            ->disabled(),
                        TextInput::make('phone_normalized')
                            ->label('Telefone')
                            ->disabled(),
                        TextInput::make('remote_jid')
                            ->label('Remote JID')
                            ->disabled(),
                        Toggle::make('from_me')
                            ->label('Enviada por mim (fromMe)')
                            ->disabled(),
                        TextInput::make('ip_address')
                            ->label('IP de origem')
                            ->disabled(),
                        TextInput::make('created_at')
                            ->label('Recebido em')
                            ->formatStateUsing(fn ($state) => DateTimeFormat::display($state instanceof \DateTimeInterface ? $state : null))
                            ->disabled(),
                        Textarea::make('message_preview')
                            ->label('Prévia da mensagem')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('processing_message')
                            ->label('Detalhe do processamento')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Payload recebido')
                    ->schema([
                        Textarea::make('payload')
                            ->label('JSON')
                            ->formatStateUsing(fn ($state) => static::formatJsonField($state))
                            ->rows(20)
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
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('instance')
                    ->label('Instância')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('processing_status')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn ($state) => $state instanceof EvolutionWebhookLogStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof EvolutionWebhookLogStatus ? $state->label() : $state),
                TextColumn::make('phone_normalized')
                    ->label('Telefone')
                    ->searchable()
                    ->placeholder('—'),
                IconColumn::make('from_me')
                    ->label('fromMe')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-up-circle')
                    ->falseIcon('heroicon-o-arrow-down-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->placeholder('—'),
                TextColumn::make('message_preview')
                    ->label('Mensagem')
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state)
                    ->placeholder('—'),
                TextColumn::make('inbound_count')
                    ->label('Válidas')
                    ->alignCenter()
                    ->placeholder('0'),
                TextColumn::make('route_slug')
                    ->label('Rota')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('processing_message')
                    ->label('Detalhe')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->filters([
                SelectFilter::make('processing_status')
                    ->label('Resultado')
                    ->options(collect(EvolutionWebhookLogStatus::cases())->mapWithKeys(
                        fn (EvolutionWebhookLogStatus $status) => [$status->value => $status->label()],
                    )),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options(fn () => EvolutionWebhookLog::query()
                        ->whereNotNull('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->all()),
                SelectFilter::make('instance')
                    ->label('Instância')
                    ->options(fn () => EvolutionWebhookLog::query()
                        ->whereNotNull('instance')
                        ->distinct()
                        ->orderBy('instance')
                        ->pluck('instance', 'instance')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvolutionWebhookLogs::route('/'),
            'view' => Pages\ViewEvolutionWebhookLog::route('/{record}'),
        ];
    }

    private static function formatJsonField(mixed $state): string
    {
        if (! filled($state)) {
            return '—';
        }

        if (is_string($state)) {
            return $state;
        }

        $encoded = json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $encoded !== false ? $encoded : '—';
    }
}
