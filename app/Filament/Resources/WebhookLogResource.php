<?php

namespace App\Filament\Resources;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use App\Filament\Resources\WebhookLogResource\Actions\ReprocessWebhookAction;
use App\Filament\Resources\WebhookLogResource\Pages;
use App\Models\WebhookLog;
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

class WebhookLogResource extends Resource
{
    protected static ?string $model = WebhookLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Logs de webhook';

    protected static ?string $modelLabel = 'log de webhook';

    protected static ?string $pluralModelLabel = 'logs de webhook';

    protected static ?int $navigationSort = 4;

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
                        TextInput::make('platform')
                            ->label('Plataforma')
                            ->formatStateUsing(fn (?string $state) => WebhookPlatform::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('event')
                            ->label('Evento')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('processing_status')
                            ->label('Resultado')
                            ->formatStateUsing(fn ($state) => $state instanceof WebhookLogStatus
                                ? $state->label()
                                : WebhookLogStatus::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('http_status')
                            ->label('HTTP')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('message')
                            ->label('Mensagem')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Dados extraídos')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('product_code')
                            ->label('Código do produto')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('external_reference')
                            ->label('Transação / referência')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('purchase_id')
                            ->label('ID da compra')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('ip_address')
                            ->label('IP de origem')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('created_at')
                            ->label('Recebido em')
                            ->formatStateUsing(fn ($state) => DateTimeFormat::display($state instanceof \DateTimeInterface ? $state : null))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Payload recebido')
                    ->schema([
                        Textarea::make('payload')
                            ->label('JSON')
                            ->formatStateUsing(fn ($state) => static::formatJsonField($state))
                            ->rows(18)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Resposta do sistema')
                    ->schema([
                        Textarea::make('response')
                            ->label('JSON')
                            ->formatStateUsing(fn ($state) => static::formatJsonField($state))
                            ->rows(8)
                            ->disabled()
                            ->dehydrated(false)
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
                    ->formatStateUsing(DateTimeFormat::filamentColumn('d/m/Y H:i:s'))
                    ->sortable(),
                TextColumn::make('platform')
                    ->label('Plataforma')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => WebhookPlatform::tryFrom($state)?->label() ?? $state),
                TextColumn::make('event')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('processing_status')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn ($state) => $state instanceof WebhookLogStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof WebhookLogStatus ? $state->label() : $state),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('product_code')
                    ->label('Produto')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('external_reference')
                    ->label('Transação')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message')
                    ->label('Mensagem')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('http_status')
                    ->label('HTTP')
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('platform')
                    ->label('Plataforma')
                    ->options(collect(WebhookPlatform::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])),
                SelectFilter::make('processing_status')
                    ->label('Resultado')
                    ->options(collect(WebhookLogStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options(fn () => WebhookLog::query()
                        ->whereNotNull('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->all()),
            ])
            ->recordActions([
                ReprocessWebhookAction::make(),
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookLogs::route('/'),
            'view' => Pages\ViewWebhookLog::route('/{record}'),
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
