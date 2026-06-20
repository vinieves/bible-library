<?php

namespace App\Filament\Resources;

use App\Enums\WhatsAppFlowStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Enums\WhatsAppFlowTriggerType;
use App\Filament\Resources\WhatsAppFlowResource\Pages;
use App\Models\WhatsAppFlow;
use App\Services\WhatsAppFlowService;
use App\Support\EvolutionInstanceOptions;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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
            ->components([
                Section::make('Configurações do Fluxo')
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
                        Placeholder::make('first_message_help')
                            ->label('Webhook Evolution (primeira mensagem)')
                            ->content(function (): HtmlString {
                                $url = IntegrationSettings::evolutionWebhookUrl();
                                $evolutionOk = IntegrationSettings::evolutionConfigured();

                                $status = $evolutionOk
                                    ? '<span class="text-success-600 dark:text-success-400">Evolution configurada.</span>'
                                    : '<span class="text-danger-600 dark:text-danger-400">Configure a Evolution em Integrações API.</span>';

                                return new HtmlString(
                                    '<div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">'.
                                    '<p>'.$status.'</p>'.
                                    '<p>Quando um <strong>contato novo</strong> enviar a <strong>primeira mensagem</strong> no WhatsApp, este fluxo será disparado <strong>uma única vez</strong> por número.</p>'.
                                    '<p><strong>URL do webhook:</strong><br><code class="text-xs break-all">'.$url.'</code></p>'.
                                    '<p><strong>Evento:</strong> <code>MESSAGES_UPSERT</code></p>'.
                                    '<p><strong>Autenticação:</strong> a Evolution envia o campo <code>apikey</code> no payload (mesma chave do painel).</p>'.
                                    '<p>Após salvar, use o botão <strong>Registrar webhook na Evolution</strong> no topo desta página.</p>'.
                                    '<p class="text-warning-600 dark:text-warning-400">Apenas <strong>um</strong> fluxo de primeira mensagem pode ficar ativo por vez.</p>'.
                                    '</div>'
                                );
                            })
                            ->visible(fn (Get $get): bool => $get('trigger_type') === WhatsAppFlowTriggerType::FirstMessage->value)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Fluxo ativo')
                            ->helperText(fn (Get $get): ?string => $get('trigger_type') === WhatsAppFlowTriggerType::FirstMessage->value
                                ? 'Ative para responder automaticamente a novos contatos do anúncio (Facebook → WhatsApp).'
                                : 'Somente fluxos ativos são disparados automaticamente')
                            ->default(false),
                        Select::make('instance_name')
                            ->label('Instância WhatsApp')
                            ->options(fn (): array => EvolutionInstanceOptions::selectOptions())
                            ->searchable()
                            ->native(false)
                            ->placeholder(fn (): string => 'Padrão: '.(IntegrationSettings::evolutionInstanceForFlows() ?: 'não definida'))
                            ->helperText(fn (Get $get): string => $get('trigger_type') === WhatsAppFlowTriggerType::FirstMessage->value
                                ? 'Número que receberá mensagens e enviará este fluxo. Deve coincidir com o webhook registrado na Evolution.'
                                : 'Instância que enviará os passos deste fluxo. Vazio usa o padrão de Integrações API.')
                            ->required(fn (Get $get): bool => (bool) $get('is_active'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Passos do Fluxo')
                    ->description('Adicione e ordene os passos que serão enviados ao contato.')
                    ->schema([
                        Repeater::make('steps')
                            ->label('')
                            ->relationship('steps')
                            ->orderColumn('order')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->addActionLabel('+ Adicionar Passo')
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo de passo')
                                    ->options(collect(WhatsAppFlowStepType::cases())->mapWithKeys(
                                        fn (WhatsAppFlowStepType $case) => [$case->value => $case->label()]
                                    ))
                                    ->default(WhatsAppFlowStepType::Text->value)
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->label('Conteúdo da Mensagem')
                                    ->toolbarButtons(['bold', 'italic', 'strike', 'bulletList', 'orderedList'])
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                                    ->dehydrated(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                                    ->required(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                                    ->columnSpanFull()
                                    ->helperText('Placeholders: {nome}, {email}, {telefone}, {producto}, {link_acceso}'),

                                Textarea::make('content')
                                    ->label('Texto da mensagem')
                                    ->rows(4)
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->dehydrated(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->required(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->columnSpanFull()
                                    ->helperText('Texto principal exibido acima dos botões.'),

                                TextInput::make('caption')
                                    ->label('Rodapé (footer)')
                                    ->maxLength(500)
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->columnSpanFull(),

                                Repeater::make('buttons')
                                    ->label('Botões interativos')
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->dehydrated(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->required(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Buttons->value)
                                    ->minItems(1)
                                    ->maxItems(3)
                                    ->defaultItems(1)
                                    ->addActionLabel('+ Adicionar botão')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Texto do botão')
                                            ->required()
                                            ->maxLength(20)
                                            ->helperText('Máximo 20 caracteres (limite WhatsApp).'),
                                        TextInput::make('button_key')
                                            ->label('ID interno')
                                            ->maxLength(50)
                                            ->helperText('Opcional. Identifica o clique (ex: btn_sim).'),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => filled($state['label'] ?? null)
                                        ? (string) $state['label']
                                        : 'Botão')
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->helperText('WhatsApp permite no máximo 3 botões do tipo resposta rápida.'),

                                Placeholder::make('delay_info')
                                    ->label('Intervalo de espera')
                                    ->content('Este passo apenas aguarda o tempo configurado antes de prosseguir.')
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Delay->value)
                                    ->columnSpanFull(),

                                TextInput::make('media_url')
                                    ->label('URL da Mídia')
                                    ->url()
                                    ->placeholder('https://exemplo.com/arquivo.jpg')
                                    ->visible(fn (Get $get): bool => in_array($get('type'), [
                                        WhatsAppFlowStepType::Image->value,
                                        WhatsAppFlowStepType::Video->value,
                                        WhatsAppFlowStepType::Audio->value,
                                        WhatsAppFlowStepType::File->value,
                                    ], true))
                                    ->required(fn (Get $get): bool => in_array($get('type'), [
                                        WhatsAppFlowStepType::Image->value,
                                        WhatsAppFlowStepType::Video->value,
                                        WhatsAppFlowStepType::Audio->value,
                                        WhatsAppFlowStepType::File->value,
                                    ], true))
                                    ->columnSpanFull()
                                    ->helperText('URL pública (JPG, PNG, GIF, WEBP para imagens — SVG não funciona no WhatsApp)'),

                                TextInput::make('caption')
                                    ->label('Legenda / Descrição')
                                    ->maxLength(1000)
                                    ->visible(fn (Get $get): bool => in_array($get('type'), [
                                        WhatsAppFlowStepType::Image->value,
                                        WhatsAppFlowStepType::Video->value,
                                        WhatsAppFlowStepType::File->value,
                                    ], true)),

                                TextInput::make('file_name')
                                    ->label('Nome do arquivo')
                                    ->placeholder('documento.pdf')
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::File->value),

                                TextInput::make('delay_seconds')
                                    ->label('Intervalo antes deste passo (seg)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(3600)
                                    ->suffix('segundos')
                                    ->helperText('Aguarda X segundos antes de executar este passo'),

                                TextInput::make('typing_delay')
                                    ->label('Delay "digitando" (seg)')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->suffix('segundos')
                                    ->visible(fn (Get $get): bool => $get('type') === WhatsAppFlowStepType::Text->value)
                                    ->helperText('Tempo que aparecerá "digitando" no WhatsApp'),
                            ])
                            ->columns(2)
                            ->itemLabel(function (array $state): ?string {
                                $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
                                $label = $type?->label() ?? 'Passo';
                                $preview = '';
                                $content = $state['content'] ?? '';

                                if (is_array($content)) {
                                    $content = collect($content)->filter(fn ($value) => filled($value))->first() ?? '';
                                }

                                if (($state['type'] ?? '') === WhatsAppFlowStepType::Text->value && filled($content)) {
                                    $preview = ' — '.Str::limit(strip_tags((string) $content), 40);
                                } elseif (($state['type'] ?? '') === WhatsAppFlowStepType::Buttons->value) {
                                    $buttonCount = is_array($state['buttons'] ?? null) ? count($state['buttons']) : 0;
                                    $preview = ' — '.($buttonCount > 0 ? "{$buttonCount} botão(ões)" : 'sem botões');

                                    if (filled($content)) {
                                        $preview .= ', '.Str::limit(strip_tags((string) $content), 30);
                                    }
                                } elseif (! empty($state['media_url'])) {
                                    $preview = ' — '.Str::limit((string) $state['media_url'], 40);
                                } elseif (($state['type'] ?? '') === WhatsAppFlowStepType::Delay->value) {
                                    $preview = ' — '.($state['delay_seconds'] ?? 0).'s';
                                }

                                return $label.$preview;
                            }),
                    ])
                    ->hiddenOn('create'),
            ]);
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
