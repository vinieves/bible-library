<?php

namespace App\Filament\Resources;

use App\Enums\WhatsAppFlowStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Enums\WhatsAppFlowTriggerType;
use App\Filament\Resources\WhatsAppFlowResource\Pages;
use App\Filament\Support\FlowStepPreview;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppMessageTrigger;
use App\Services\WhatsAppFlowService;
use App\Support\EvolutionInstanceOptions;
use App\Support\IntegrationSettings;
use App\Support\WhatsAppFlowStepMedia;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use RuntimeException;
use UnitEnum;

class WhatsAppFlowResource extends Resource
{
    protected static ?string $model = WhatsAppFlow::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Fluxos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'fluxo';

    protected static ?string $pluralModelLabel = 'fluxos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 12])
            ->components([
                Section::make('Configurações do Fluxo')
                    ->extraAttributes(['class' => 'flow-builder-config'])
                    ->columnSpan(['default' => 1, 'lg' => 4])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome do Fluxo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpan(2),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(WhatsAppFlowStatus::cases())->mapWithKeys(
                                fn (WhatsAppFlowStatus $status) => [$status->value => $status->label()]
                            ))
                            ->default(WhatsAppFlowStatus::Draft->value)
                            ->required(),
                        Select::make('trigger_type')
                            ->label('Gatilho')
                            ->options(collect(WhatsAppFlowTriggerType::cases())->mapWithKeys(
                                fn (WhatsAppFlowTriggerType $type) => [$type->value => $type->label()]
                            ))
                            ->default(WhatsAppFlowTriggerType::Manual->value)
                            ->live()
                            ->required(),
                        Select::make('trigger_event')
                            ->label('Evento (Hotmart)')
                            ->options([
                                'PURCHASE_APPROVED' => 'Venda aprovada',
                                'PURCHASE_PROTEST' => 'Pedido de reembolso',
                                'PURCHASE_CANCELED' => 'Venda cancelada',
                                'PURCHASE_CHARGEBACK' => 'Chargeback',
                                'PURCHASE_COMPLETE' => 'Compra completa',
                                'PURCHASE_OUT_OF_SHOPPING_CART' => 'Abandonou carrinho',
                            ])
                            ->visible(fn (Get $get): bool => $get('trigger_type') === WhatsAppFlowTriggerType::Webhook->value),
                        Select::make('message_trigger_id')
                            ->label('Gatilho de mensagem')
                            ->relationship(
                                name: 'messageTrigger',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (WhatsAppMessageTrigger $record): string => "{$record->public_code} — {$record->name}"
                            )
                            ->searchable(['name', 'public_code', 'message'])
                            ->preload()
                            ->native(false)
                            ->required(fn (Get $get): bool => $get('trigger_type') === WhatsAppFlowTriggerType::MessageTrigger->value)
                            ->visible(fn (Get $get): bool => $get('trigger_type') === WhatsAppFlowTriggerType::MessageTrigger->value)
                            ->helperText('Selecione o gatilho cujo texto coincide com a primeira mensagem do anúncio. Cadastre em Sistema → Gatilhos.')
                            ->columnSpanFull(),
                        View::make('filament.resources.whatsapp-flow.first-message-webhook-help')
                            ->visible(fn (Get $get): bool => in_array($get('trigger_type'), [
                                WhatsAppFlowTriggerType::FirstMessage->value,
                                WhatsAppFlowTriggerType::MessageTrigger->value,
                            ], true))
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Fluxo ativo')
                            ->helperText(fn (Get $get): ?string => match ($get('trigger_type')) {
                                WhatsAppFlowTriggerType::FirstMessage->value => 'Ative para responder automaticamente a novos contatos do anúncio (Facebook → WhatsApp).',
                                WhatsAppFlowTriggerType::MessageTrigger->value => 'Ative para disparar este fluxo quando a primeira mensagem bater com o gatilho selecionado.',
                                default => 'Somente fluxos ativos são disparados automaticamente',
                            })
                            ->default(false),
                        Select::make('instance_name')
                            ->label('Instância WhatsApp')
                            ->options(fn (): array => EvolutionInstanceOptions::selectOptions())
                            ->searchable()
                            ->native(false)
                            ->placeholder(fn (): string => 'Padrão: '.(IntegrationSettings::evolutionInstanceForFlows() ?: 'não definida'))
                            ->helperText(fn (Get $get): string => in_array($get('trigger_type'), [
                                WhatsAppFlowTriggerType::FirstMessage->value,
                                WhatsAppFlowTriggerType::MessageTrigger->value,
                            ], true)
                                ? 'Número que receberá mensagens e enviará este fluxo. Deve coincidir com o webhook registrado na Evolution.'
                                : 'Instância que enviará os passos deste fluxo. Vazio usa o padrão de Integrações API.')
                            ->required(fn (Get $get): bool => (bool) $get('is_active'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Passos do Fluxo')
                    ->description('')
                    ->extraAttributes(['class' => 'flow-builder-section'])
                    ->columnSpan(['default' => 1, 'lg' => 8])
                    ->schema([
                        View::make('filament.resources.whatsapp-flow.builder-styles')
                            ->columnSpanFull(),

                        Placeholder::make('steps_pipeline_title')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<div class="flow-pipeline-title">'.
                                '<span class="flow-pipeline-title__bar" aria-hidden="true"></span>'.
                                '<span>Passos</span>'.
                                '</div>'
                            ))
                            ->columnSpanFull(),

                        Repeater::make('steps')
                            ->label('')
                            ->hiddenLabel()
                            ->relationship('steps')
                            ->orderColumn('order')
                            ->reorderableWithDragAndDrop()
                            ->reorderAction(fn (Action $action) => $action->icon(Heroicon::Bars2))
                            ->collapsible()
                            ->collapsed()
                            ->cloneable(false)
                            ->addActionLabel('+ Adicionar passo')
                            ->addActionAlignment(Alignment::Start)
                            ->collapseAllAction(fn (Action $action) => $action->hidden())
                            ->expandAllAction(fn (Action $action) => $action->hidden())
                            ->truncateItemLabel(false)
                            ->extraAttributes(['class' => 'flow-steps-repeater'])
                            ->schema(static::stepSchema())
                            ->itemLabel(fn (array $state): HtmlString => FlowStepPreview::itemLabel($state)),
                    ])
                    ->hiddenOn('create'),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Field>
     */
    public static function stepSchema(): array
    {
        return [
            Select::make('type')
                ->label('Tipo')
                ->options(collect(WhatsAppFlowStepType::cases())->mapWithKeys(
                    fn (WhatsAppFlowStepType $case) => [$case->value => $case->label()]
                ))
                ->default(WhatsAppFlowStepType::Text->value)
                ->required()
                ->native(false)
                ->live()
                ->columnSpanFull(),

            RichEditor::make('content')
                ->label('Texto 1')
                ->toolbarButtons(['bold', 'italic', 'strike'])
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                ->required(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                ->columnSpanFull(),

            RichEditor::make('content_variation_2')
                ->label('Texto 2')
                ->toolbarButtons(['bold', 'italic', 'strike'])
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                ->columnSpanFull(),

            RichEditor::make('content_variation_3')
                ->label('Texto 3')
                ->toolbarButtons(['bold', 'italic', 'strike'])
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                ->columnSpanFull(),

            Placeholder::make('text_variations_help')
                ->hiddenLabel()
                ->content('Textos 2 e 3 são opcionais. A cada novo cliente, o envio alterna entre as variações preenchidas (ex.: 1→A, 2→B, 3→A…). Use <code>{nome}</code> para o primeiro nome do contato (pushName do WhatsApp).')
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                ->columnSpanFull(),

            Placeholder::make('delay_info')
                ->label('Intervalo de espera')
                ->content('Este passo pausa o fluxo pelo tempo configurado abaixo antes de continuar.')
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Delay->value)
                ->columnSpanFull(),

            Placeholder::make('wait_for_response_info')
                ->hiddenLabel()
                ->content('O fluxo pausa aqui até o contato enviar qualquer mensagem. Ao receber a resposta (webhook), continua automaticamente para o próximo passo — sem disparar novamente o fluxo de primeira mensagem.')
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::WaitForResponse->value)
                ->columnSpanFull(),

            TextInput::make('delay_seconds')
                ->label('Espera antes (seg)')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(3600)
                ->suffix('s')
                ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::WaitForResponse->value)
                ->helperText('Opcional: pausa antes de começar a aguardar a resposta.')
                ->columnSpanFull(),

            TextInput::make('media_url')
                ->hidden()
                ->dehydrated(),

            FileUpload::make('media_path')
                ->label(fn (Get $get): string => match (WhatsAppFlowStepType::tryFrom($get('type') ?? '')) {
                    WhatsAppFlowStepType::Image => 'Imagem',
                    WhatsAppFlowStepType::Video => 'Vídeo',
                    WhatsAppFlowStepType::Audio => 'Áudio',
                    WhatsAppFlowStepType::File => 'Arquivo',
                    default => 'Arquivo',
                })
                ->disk('public')
                ->visibility('public')
                ->multiple(false)
                ->panelLayout('compact')
                ->uploadButtonPosition('center bottom')
                ->placeholder('Arraste o arquivo aqui ou clique para selecionar')
                ->directory(fn ($livewire): string => WhatsAppFlowStepMedia::uploadDirectory(
                    method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->getKey() : null
                ))
                ->acceptedFileTypes(fn (Get $get): array => WhatsAppFlowStepMedia::acceptedMimeTypes($get('type')))
                ->mimeTypeMap(fn (Get $get): array => WhatsAppFlowStepMedia::mimeTypeMap($get('type')))
                ->maxSize(fn (Get $get): int => WhatsAppFlowStepMedia::maxUploadSizeKb($get('type')))
                ->openable()
                ->downloadable()
                ->previewable(false)
                ->storeFileNamesIn('file_name')
                ->visible(fn (Get $get): bool => static::isMediaStepType($get('type')))
                ->required(fn (Get $get): bool => static::isMediaStepType($get('type'))
                    && blank($get('media_path'))
                    && blank($get('media_url')))
                ->columnSpanFull()
                ->helperText(fn (Get $get): string => match (WhatsAppFlowStepType::tryFrom($get('type') ?? '')) {
                    WhatsAppFlowStepType::Image => 'JPG, PNG, GIF ou WEBP — até 5 MB.',
                    WhatsAppFlowStepType::Video => 'MP4 ou WEBM — até 16 MB.',
                    WhatsAppFlowStepType::Audio => 'MP3, MPEG, OGG ou M4A — até 16 MB.',
                    WhatsAppFlowStepType::File => 'PDF, DOC, DOCX, XLS, XLSX, ZIP ou TXT — até 100 MB.',
                    default => 'Formato compatível com WhatsApp / Evolution API.',
                }),

            Grid::make(2)
                ->schema([
                    TextInput::make('caption')
                        ->label('Legenda')
                        ->maxLength(1000)
                        ->helperText('Use {nome} para o primeiro nome do contato.')
                        ->visible(fn (Get $get): bool => in_array($get('type'), [
                            WhatsAppFlowStepType::Image->value,
                            WhatsAppFlowStepType::Video->value,
                            WhatsAppFlowStepType::File->value,
                        ], true))
                        ->dehydrated(fn (Get $get): bool => in_array($get('type'), [
                            WhatsAppFlowStepType::Image->value,
                            WhatsAppFlowStepType::Video->value,
                            WhatsAppFlowStepType::File->value,
                        ], true)),

                    TextInput::make('file_name')
                        ->label('Nome do arquivo')
                        ->placeholder('documento.pdf')
                        ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::File->value),
                ])
                ->visible(fn (Get $get): bool => in_array($get('type'), [
                    WhatsAppFlowStepType::Image->value,
                    WhatsAppFlowStepType::Video->value,
                    WhatsAppFlowStepType::File->value,
                ], true)),

            Grid::make(2)
                ->schema([
                    TextInput::make('delay_seconds')
                        ->label('Espera antes (seg)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(3600)
                        ->suffix('s')
                        ->visible(fn (Get $get): bool => in_array($get('type'), [
                            WhatsAppFlowStepType::Text->value,
                            WhatsAppFlowStepType::Image->value,
                            WhatsAppFlowStepType::Video->value,
                            WhatsAppFlowStepType::Audio->value,
                            WhatsAppFlowStepType::File->value,
                            WhatsAppFlowStepType::Delay->value,
                        ], true))
                        ->helperText('Pausa antes de executar este passo'),

                    TextInput::make('typing_delay')
                        ->label('Digitando (seg)')
                        ->numeric()
                        ->default(3)
                        ->minValue(0)
                        ->maxValue(60)
                        ->suffix('s')
                        ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                        ->helperText('Simula "digitando..." no WhatsApp'),

                    TextInput::make('recording_delay')
                        ->label('Gravando (seg)')
                        ->numeric()
                        ->default(3)
                        ->minValue(0)
                        ->maxValue(120)
                        ->suffix('s')
                        ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Audio->value)
                        ->helperText('Simula "gravando áudio..." no WhatsApp antes de enviar'),
                ]),
        ];
    }

    public static function isMediaStepType(mixed $type): bool
    {
        return in_array($type, [
            WhatsAppFlowStepType::Image->value,
            WhatsAppFlowStepType::Video->value,
            WhatsAppFlowStepType::Audio->value,
            WhatsAppFlowStepType::File->value,
        ], true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppFlowStatus
                        ? $state->label()
                        : WhatsAppFlowStatus::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn ($state) => $state instanceof WhatsAppFlowStatus
                        ? $state->color()
                        : WhatsAppFlowStatus::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('steps_count')
                    ->label('Blocos')
                    ->suffix(' blocos')
                    ->icon('heroicon-o-bolt'),
                TextColumn::make('trigger_type')
                    ->label('Gatilho')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppFlowTriggerType
                        ? $state->label()
                        : WhatsAppFlowTriggerType::tryFrom((string) $state)?->label() ?? $state),
                TextColumn::make('messageTrigger.public_code')
                    ->label('Gatilho ID')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('instance_name')
                    ->label('Instância')
                    ->placeholder(fn (WhatsAppFlow $record): string => $record->resolveInstanceName() ?: '—')
                    ->formatStateUsing(fn (?string $state, WhatsAppFlow $record): string => filled($state)
                        ? $state
                        : ($record->resolveInstanceName() ?: '—'))
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Editar Fluxo'),
                Action::make('dispatch_test')
                    ->label('Enviar Teste')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (): bool => IntegrationSettings::evolutionApiReady())
                    ->schema([
                        TextInput::make('phone')
                            ->label('Número de telefone')
                            ->placeholder('5511999999999')
                            ->required(),
                    ])
                    ->action(function (WhatsAppFlow $record, array $data, WhatsAppFlowService $flowService): void {
                        try {
                            $flowService->dispatch(
                                flow: $record,
                                phone: $data['phone'],
                                trigger: 'manual',
                                userId: auth()->id(),
                            );
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title('Não foi possível enfileirar')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Fluxo enfileirado!')
                            ->body('Verifique Execuções de Fluxo após o worker processar.')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppFlows::route('/'),
            'create' => Pages\CreateWhatsAppFlow::route('/create'),
            'edit' => Pages\EditWhatsAppFlow::route('/{record}/edit'),
        ];
    }
}
