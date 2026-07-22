<?php

namespace App\Filament\Resources;

use App\Enums\EmailBroadcastStatus;
use App\Filament\Resources\EmailBroadcastResource\Pages;
use App\Models\EmailBroadcast;
use App\Services\EmailBroadcastAudienceService;
use App\Services\EmailBroadcastDispatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

class EmailBroadcastResource extends Resource
{
    protected static ?string $model = EmailBroadcast::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'E-mail';

    protected static ?string $navigationLabel = 'Campanhas';

    protected static ?string $modelLabel = 'campanha';

    protected static ?string $pluralModelLabel = 'campanhas';

    protected static ?int $navigationSort = 4;

    /**
     * Segmentos de situação de login (rótulos reutilizados no form e no model).
     *
     * @return array<string, string>
     */
    public static function loginSegments(): array
    {
        return [
            'all' => 'Todos os registrados',
            'dormant' => 'Sumidos (30 dias+ ou nunca)',
            'never' => 'Nunca logaram',
            'active7' => 'Ativos (últimos 7 dias)',
            'new7' => 'Cadastrados nos últimos 7 dias',
        ];
    }

    /**
     * Normaliza os campos de público antes de gravar: converte a lista de e-mails
     * (texto) em array e zera campos irrelevantes ao tipo de público escolhido.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeAudienceData(array $data, ?int $createdBy = null): array
    {
        $type = $data['audience_type'] ?? 'all';

        $data['audience_segment'] = $type === 'login_segment' ? ($data['audience_segment'] ?? 'all') : null;

        $data['email_list'] = $type === 'email_list'
            ? app(EmailBroadcastAudienceService::class)->normalizeEmails((string) ($data['email_list'] ?? ''))
            : null;

        if ($createdBy !== null) {
            $data['created_by'] = $createdBy;
        }

        return $data;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conteúdo')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Assunto')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Placeholder::make('placeholders_docs')
                            ->label('Personalização')
                            ->content(new HtmlString(
                                'Use no assunto ou no corpo: <code>{nome}</code>, <code>{email}</code>, '
                                .'<code>{link_acceso}</code> (link de acesso/login). Serão substituídos por destinatário.'
                            ))
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->label('Corpo do e-mail')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('email-broadcasts')
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->label('Anexos')
                            ->multiple()
                            ->disk('public')
                            ->directory('email-broadcasts/attachments')
                            ->visibility('public')
                            ->maxSize(10240)
                            ->maxFiles(10)
                            ->previewable()
                            ->openable()
                            ->downloadable()
                            ->reorderable()
                            ->helperText('PDF, imagens, Word ou outros arquivos (máx. 10 MB cada). Enviados como anexo em cada e-mail.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Público')
                    ->schema([
                        Select::make('audience_type')
                            ->label('Enviar para')
                            ->options([
                                'all' => 'Todos os registrados',
                                'login_segment' => 'Por situação de login',
                                'email_list' => 'Colar lista de e-mails',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Select::make('audience_segment')
                            ->label('Situação de login')
                            ->options(self::loginSegments())
                            ->default('all')
                            ->live()
                            ->visible(fn (Get $get): bool => $get('audience_type') === 'login_segment')
                            ->columnSpanFull(),
                        Textarea::make('email_list')
                            ->label('E-mails (um por linha)')
                            ->helperText('Só serão considerados e-mails que já têm conta cadastrada.')
                            ->rows(8)
                            ->live(debounce: 600)
                            ->visible(fn (Get $get): bool => $get('audience_type') === 'email_list')
                            ->columnSpanFull(),
                        Toggle::make('exclude_admins')
                            ->label('Não enviar para administradores')
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),
                        Placeholder::make('recipient_preview')
                            ->label('Destinatários estimados')
                            ->content(function (Get $get): HtmlString {
                                $count = app(EmailBroadcastAudienceService::class)->countFromState(
                                    audienceType: $get('audience_type'),
                                    segment: $get('audience_segment'),
                                    emailList: (string) ($get('email_list') ?? ''),
                                    excludeAdmins: (bool) $get('exclude_admins'),
                                );

                                return new HtmlString('<strong>≈ '.$count.'</strong> destinatário(s)');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('audience_label')
                    ->label('Público')
                    ->state(fn (EmailBroadcast $record): string => $record->audienceLabel()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (EmailBroadcastStatus $state): string => $state->label())
                    ->color(fn (EmailBroadcastStatus $state): string => $state->color()),
                TextColumn::make('progress')
                    ->label('Enviados')
                    ->state(fn (EmailBroadcast $record): string => $record->sent_count.'/'.$record->total_recipients
                        .($record->failed_count > 0 ? " ({$record->failed_count} falhas)" : '')),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => collect(EmailBroadcastStatus::cases())
                        ->mapWithKeys(fn (EmailBroadcastStatus $s): array => [$s->value => $s->label()])
                        ->all()),
            ])
            ->recordActions([
                self::testAction(),
                self::dispatchAction(),
                \Filament\Actions\EditAction::make()
                    ->visible(fn (EmailBroadcast $record): bool => $record->isDraft()),
            ]);
    }

    public static function testAction(): Action
    {
        return Action::make('sendTest')
            ->label('Enviar teste')
            ->icon('heroicon-o-beaker')
            ->color('gray')
            ->form([
                TextInput::make('email')
                    ->label('Enviar teste para')
                    ->email()
                    ->required()
                    ->default(fn (): ?string => Auth::user()?->email),
            ])
            ->action(function (EmailBroadcast $record, array $data, EmailBroadcastDispatcher $dispatcher): void {
                $dispatcher->sendTest($record, $data['email'], Auth::user());

                Notification::make()
                    ->title('E-mail de teste enfileirado')
                    ->body('Enviado para '.$data['email'].'. Confira a caixa de entrada em instantes.')
                    ->success()
                    ->send();
            });
    }

    public static function dispatchAction(): Action
    {
        return Action::make('dispatch')
            ->label('Disparar campanha')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->visible(fn (EmailBroadcast $record): bool => $record->isDraft())
            ->requiresConfirmation()
            ->modalHeading('Disparar campanha')
            ->modalDescription(fn (EmailBroadcast $record): string => 'Serão enviados e-mails para ≈ '
                .app(EmailBroadcastAudienceService::class)->count($record)
                .' destinatário(s). Esta ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Disparar agora')
            ->action(function (EmailBroadcast $record, EmailBroadcastDispatcher $dispatcher): void {
                $total = $dispatcher->dispatch($record);

                Notification::make()
                    ->title('Campanha enfileirada')
                    ->body($total.' e-mail(s) na fila de envio.')
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailBroadcasts::route('/'),
            'create' => Pages\CreateEmailBroadcast::route('/create'),
            'edit' => Pages\EditEmailBroadcast::route('/{record}/edit'),
        ];
    }
}
