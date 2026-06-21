<?php

namespace App\Filament\Resources;

use App\Enums\EvolutionRegistryEventDirection;
use App\Filament\Resources\EvolutionRegistryContactResource\Pages;
use App\Models\EvolutionRegistryContact;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class EvolutionRegistryContactResource extends Resource
{
    protected static ?string $model = EvolutionRegistryContact::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Registro Geral';

    protected static ?string $modelLabel = 'contato Evolution';

    protected static ?string $pluralModelLabel = 'Registro Geral';

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
                Section::make('Contato')
                    ->schema([
                        TextInput::make('phone_normalized')
                            ->label('Telefone')
                            ->disabled(),
                        TextInput::make('contact_name')
                            ->label('Nome (pushName)')
                            ->disabled(),
                        TextInput::make('instance_name')
                            ->label('Instância WhatsApp')
                            ->disabled(),
                        TextInput::make('remote_jid')
                            ->label('Remote JID')
                            ->disabled(),
                        Toggle::make('has_inbound_contact')
                            ->label('Primeira mensagem registrada')
                            ->disabled(),
                        TextInput::make('events_count')
                            ->label('Total de eventos')
                            ->disabled(),
                        TextInput::make('inbound_count')
                            ->label('Mensagens recebidas')
                            ->disabled(),
                        TextInput::make('outbound_count')
                            ->label('Mensagens enviadas')
                            ->disabled(),
                        TextInput::make('flow_executions_count')
                            ->label('Execuções de fluxo')
                            ->disabled(),
                        TextInput::make('first_seen_at')
                            ->label('Primeiro evento')
                            ->formatStateUsing(fn ($state) => static::formatDateTime($state))
                            ->disabled(),
                        TextInput::make('last_event_at')
                            ->label('Último evento')
                            ->formatStateUsing(fn ($state) => static::formatDateTime($state))
                            ->disabled(),
                        TextInput::make('last_inbound_at')
                            ->label('Última mensagem recebida')
                            ->formatStateUsing(fn ($state) => static::formatDateTime($state))
                            ->disabled(),
                        TextInput::make('last_outbound_at')
                            ->label('Última mensagem enviada')
                            ->formatStateUsing(fn ($state) => static::formatDateTime($state))
                            ->disabled(),
                        Textarea::make('last_message_preview')
                            ->label('Última mensagem')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Execuções de fluxo')
                    ->schema([
                        Textarea::make('flow_executions_summary')
                            ->label('')
                            ->rows(8)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Histórico de eventos (Evolution webhook)')
                    ->description('Linha do tempo completa recebida da Evolution API, separada por instância e telefone.')
                    ->schema([
                        Textarea::make('events_timeline')
                            ->label('')
                            ->rows(24)
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
                TextColumn::make('last_event_at')
                    ->label('Último evento')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('phone_normalized')
                    ->label('Telefone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Nome')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('instance_name')
                    ->label('Instância')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('inbound_count')
                    ->label('Recebidas')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('outbound_count')
                    ->label('Enviadas')
                    ->alignCenter()
                    ->sortable(),
                IconColumn::make('has_inbound_contact')
                    ->label('1ª msg')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (bool $state): string => $state
                        ? 'Primeira mensagem já registrada (fluxo pode ter sido disparado)'
                        : 'Ainda sem registro de primeira mensagem'),
                TextColumn::make('flow_executions_count')
                    ->label('Fluxos')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('events_count')
                    ->label('Eventos')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('last_message_preview')
                    ->label('Última mensagem')
                    ->limit(35)
                    ->tooltip(fn (?string $state) => $state)
                    ->placeholder('—'),
            ])
            ->defaultSort('last_event_at', 'desc')
            ->poll('15s')
            ->filters([
                SelectFilter::make('instance_name')
                    ->label('Instância')
                    ->options(fn () => EvolutionRegistryContact::query()
                        ->distinct()
                        ->orderBy('instance_name')
                        ->pluck('instance_name', 'instance_name')
                        ->all()),
                TernaryFilter::make('has_inbound_contact')
                    ->label('Primeira mensagem registrada'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvolutionRegistryContacts::route('/'),
            'view' => Pages\ViewEvolutionRegistryContact::route('/{record}'),
        ];
    }

    public static function formatDateTime(mixed $state): ?string
    {
        if ($state instanceof \DateTimeInterface) {
            return $state->format('d/m/Y H:i:s');
        }

        return filled($state) ? (string) $state : '—';
    }

    public static function directionLabel(mixed $state): string
    {
        if ($state instanceof EvolutionRegistryEventDirection) {
            return $state->label();
        }

        return EvolutionRegistryEventDirection::tryFrom((string) $state)?->label() ?? (string) $state;
    }
}
