<?php

namespace App\Filament\Resources;

use App\Enums\EmailDispatchStatus;
use App\Enums\EmailDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Filament\Resources\EmailDispatchLogResource\Pages;
use App\Models\EmailDispatchLog;
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

class EmailDispatchLogResource extends Resource
{
    protected static ?string $model = EmailDispatchLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string | UnitEnum | null $navigationGroup = 'E-mail';

    protected static ?string $navigationLabel = 'Histórico';

    protected static ?string $modelLabel = 'disparo de e-mail';

    protected static ?string $pluralModelLabel = 'disparos de e-mail';

    protected static ?int $navigationSort = 3;

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
                        TextInput::make('trigger')
                            ->label('Canal')
                            ->formatStateUsing(fn ($state) => $state instanceof EmailDispatchTrigger
                                ? $state->label()
                                : EmailDispatchTrigger::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Resultado')
                            ->formatStateUsing(fn ($state) => $state instanceof EmailDispatchStatus
                                ? $state->label()
                                : EmailDispatchStatus::tryFrom((string) $state)?->label() ?? $state)
                            ->disabled(),
                        TextInput::make('from_address')
                            ->label('Remetente')
                            ->disabled(),
                        TextInput::make('recipient_email')
                            ->label('Destinatário')
                            ->disabled(),
                        TextInput::make('user_email')
                            ->label('Usuário')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('purchase_id')
                            ->label('ID da compra')
                            ->disabled(),
                        TextInput::make('hotmart_transaction')
                            ->label('Transação Hotmart')
                            ->disabled(),
                        TextInput::make('attempt')
                            ->label('Tentativa')
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
                Section::make('E-mail')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Assunto')
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Corpo enviado')
                            ->rows(10)
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
                    ->formatStateUsing(DateTimeFormat::filamentColumn('d/m/Y H:i:s'))
                    ->sortable(),
                TextColumn::make('message_event')
                    ->label('Origem')
                    ->badge()
                    ->color(fn (EmailDispatchLog $record) => WhatsAppMessageEvent::resolveOriginColor(
                        $record->message_event?->value,
                    ))
                    ->formatStateUsing(fn (EmailDispatchLog $record) => WhatsAppMessageEvent::resolveOrigin(
                        $record->message_event?->value,
                        null,
                    ))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn ($state) => $state instanceof EmailDispatchStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof EmailDispatchStatus ? $state->label() : $state),
                TextColumn::make('recipient_email')
                    ->label('Destinatário')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Assunto')
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state)
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
                    ->options(collect(EmailDispatchStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailDispatchLogs::route('/'),
            'view' => Pages\ViewEmailDispatchLog::route('/{record}'),
        ];
    }
}
