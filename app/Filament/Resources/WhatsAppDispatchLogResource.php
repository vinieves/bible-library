<?php

namespace App\Filament\Resources;

use App\Enums\WhatsAppDispatchStatus;
use App\Enums\WhatsAppDispatchTrigger;
use App\Filament\Resources\WhatsAppDispatchLogResource\Pages;
use App\Models\WhatsAppDispatchLog;
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
                        TextInput::make('trigger')
                            ->label('Origem')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchTrigger ? $state->label() : $state)
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Resultado')
                            ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchStatus ? $state->label() : $state)
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Telefone informado')
                            ->disabled(),
                        TextInput::make('phone_normalized')
                            ->label('Telefone normalizado')
                            ->disabled(),
                        TextInput::make('user.email')
                            ->label('Usuário')
                            ->disabled(),
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
                            ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i:s'))
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
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
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
                TextColumn::make('trigger')
                    ->label('Origem')
                    ->badge()
                    ->color(fn ($state) => $state === WhatsAppDispatchTrigger::ManualTest ? 'info' : 'primary')
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppDispatchTrigger ? $state->label() : $state),
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
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('trigger')
                    ->label('Origem')
                    ->options(collect(WhatsAppDispatchTrigger::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
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
