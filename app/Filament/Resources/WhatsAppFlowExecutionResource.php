<?php

namespace App\Filament\Resources;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Filament\Resources\WhatsAppFlowExecutionResource\Pages;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppFlowExecutionResource extends Resource
{
    protected static ?string $model = WhatsAppFlowExecution::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Execuções de Fluxo';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'execução de fluxo';

    protected static ?string $pluralModelLabel = 'execuções de fluxo';

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
                        TextInput::make('flow.name')
                            ->label('Fluxo')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppFlowExecutionStatus
                                ? $state->label()
                                : WhatsAppFlowExecutionStatus::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('phone_normalized')
                            ->label('Telefone')
                            ->disabled(),
                        TextInput::make('trigger')
                            ->label('Gatilho')
                            ->disabled(),
                        TextInput::make('progress')
                            ->label('Passos')
                            ->formatStateUsing(fn (?string $state, WhatsAppFlowExecution $record): string => "{$record->current_step}/{$record->total_steps}")
                            ->disabled(),
                        TextInput::make('started_at')
                            ->label('Iniciado em')
                            ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i:s') ?? '—')
                            ->disabled(),
                        TextInput::make('completed_at')
                            ->label('Concluído em')
                            ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i:s') ?? '—')
                            ->disabled(),
                        Textarea::make('error_message')
                            ->label('Erro geral')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Log por passo')
                    ->schema([
                        Textarea::make('steps_log')
                            ->label('')
                            ->formatStateUsing(function (?string $state, WhatsAppFlowExecution $record): string {
                                $lines = $record->logs
                                    ->sortBy('step_order')
                                    ->map(function (WhatsAppFlowExecutionLog $log): string {
                                        $type = WhatsAppFlowStepType::tryFrom($log->step_type)?->label() ?? $log->step_type;
                                        $status = $log->status instanceof WhatsAppFlowExecutionLogStatus
                                            ? $log->status->label()
                                            : (string) $log->status;
                                        $http = $log->http_status ? "HTTP {$log->http_status}" : '—';
                                        $error = $log->error_message ? " | Erro: {$log->error_message}" : '';
                                        $response = filled($log->evolution_response)
                                            ? "\n   Resposta: ".json_encode($log->evolution_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : '';

                                        return "#{$log->step_order} {$type} — {$status} ({$http}){$error}{$response}";
                                    });

                                return $lines->isEmpty() ? 'Nenhum passo registrado.' : $lines->implode("\n\n");
                            })
                            ->rows(16)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flow.name')
                    ->label('Fluxo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_normalized')
                    ->label('Telefone')
                    ->searchable(['phone', 'phone_normalized']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppFlowExecutionStatus
                        ? $state->label()
                        : WhatsAppFlowExecutionStatus::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn ($state) => $state instanceof WhatsAppFlowExecutionStatus
                        ? $state->color()
                        : WhatsAppFlowExecutionStatus::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('progress')
                    ->label('Passos')
                    ->state(fn (WhatsAppFlowExecution $record): string => "{$record->current_step}/{$record->total_steps}"),
                TextColumn::make('trigger')
                    ->label('Gatilho')
                    ->badge(),
                TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('completed_at')
                    ->label('Concluído')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('error_message')
                    ->label('Erro')
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppFlowExecutions::route('/'),
            'view' => Pages\ViewWhatsAppFlowExecution::route('/{record}'),
        ];
    }
}
