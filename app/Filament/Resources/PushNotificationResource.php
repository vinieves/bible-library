<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationResource\Pages;
use App\Models\PushNotification;
use App\Services\PushNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string | UnitEnum | null $navigationGroup = 'Notificações';

    protected static ?string $navigationLabel = 'Notificações';

    protected static ?string $modelLabel = 'notificação';

    protected static ?string $pluralModelLabel = 'notificações';

    protected static ?int $navigationSort = 1;

    /** @var array<int, string> */
    public static function weekdayOptions(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conteúdo')
                    ->description('Enviado para todos que ativaram as notificações no app.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(80),
                        Textarea::make('body')
                            ->label('Mensagem')
                            ->required()
                            ->rows(3)
                            ->maxLength(300),
                        TextInput::make('url')
                            ->label('Link ao clicar (opcional)')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Para onde o usuário vai ao tocar na notificação. Vazio = abre o app.'),
                        FileUpload::make('icon')
                            ->label('Ícone (imagem, opcional)')
                            ->image()
                            ->disk('public')
                            ->directory('push-icons')
                            ->imageEditor()
                            ->maxSize(1024)
                            ->helperText('Ideal quadrada (ex.: 192×192). Vazio = usa o ícone padrão do app.'),
                    ])
                    ->columns(1),
                Section::make('Agendamento')
                    ->schema([
                        Radio::make('schedule_type')
                            ->label('Quando enviar')
                            ->options([
                                'now' => 'Enviar agora',
                                'once' => 'Agendar (data e hora)',
                                'recurring' => 'Recorrente',
                            ])
                            ->default('now')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        DateTimePicker::make('scheduled_at')
                            ->label('Data e hora do envio')
                            ->seconds(false)
                            ->required(fn (Get $get): bool => $get('schedule_type') === 'once')
                            ->visible(fn (Get $get): bool => $get('schedule_type') === 'once'),
                        Select::make('recurrence_frequency')
                            ->label('Frequência')
                            ->options([
                                'daily' => 'Diária',
                                'weekly' => 'Semanal',
                            ])
                            ->native(false)
                            ->live()
                            ->required(fn (Get $get): bool => $get('schedule_type') === 'recurring')
                            ->visible(fn (Get $get): bool => $get('schedule_type') === 'recurring'),
                        TimePicker::make('recurrence_time')
                            ->label('Horário')
                            ->seconds(false)
                            ->required(fn (Get $get): bool => $get('schedule_type') === 'recurring')
                            ->visible(fn (Get $get): bool => $get('schedule_type') === 'recurring'),
                        CheckboxList::make('recurrence_days')
                            ->label('Dias da semana')
                            ->options(static::weekdayOptions())
                            ->columns(3)
                            ->required(fn (Get $get): bool => $get('schedule_type') === 'recurring' && $get('recurrence_frequency') === 'weekly')
                            ->visible(fn (Get $get): bool => $get('schedule_type') === 'recurring' && $get('recurrence_frequency') === 'weekly')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('schedule_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'now' => 'Imediata',
                        'once' => 'Agendada',
                        'recurring' => 'Recorrente',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'recurring' => 'info',
                        'once' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Agendada',
                        'sending' => 'Enviando',
                        'sent' => 'Enviada',
                        'failed' => 'Falhou',
                        'canceled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'sending' => 'info',
                        'scheduled' => 'warning',
                        'failed' => 'danger',
                        'canceled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Agendada p/')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_sent_at')
                    ->label('Último envio')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sent_count')
                    ->label('Enviadas')
                    ->numeric(),
                TextColumn::make('failed_count')
                    ->label('Falhas')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('sendNow')
                    ->label('Enviar agora')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar esta notificação agora?')
                    ->modalDescription('Será enfileirada para todos os inscritos imediatamente.')
                    ->visible(fn (PushNotification $record): bool => $record->status !== 'sending')
                    ->action(function (PushNotification $record): void {
                        app(PushNotificationService::class)->dispatch($record);

                        Notification::make()
                            ->title('Notificação enfileirada')
                            ->body('Será entregue aos inscritos em instantes.')
                            ->success()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (PushNotification $record): bool => $record->status === 'scheduled')
                    ->action(function (PushNotification $record): void {
                        $record->update(['status' => 'canceled']);

                        Notification::make()
                            ->title('Agendamento cancelado')
                            ->success()
                            ->send();
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
            'index' => Pages\ListPushNotifications::route('/'),
            'create' => Pages\CreatePushNotification::route('/create'),
            'edit' => Pages\EditPushNotification::route('/{record}/edit'),
        ];
    }
}
