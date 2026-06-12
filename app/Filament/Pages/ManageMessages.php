<?php

namespace App\Filament\Pages;

use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendWelcomeWhatsAppJob;
use App\Models\WhatsAppMessageTemplate;
use App\Services\Webhooks\PhoneNumber;
use App\Services\WhatsAppMessageTemplateService;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ManageMessages extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Mensagens';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public bool $showRuleForm = false;

    public ?string $editingEvent = null;

    /** @var Collection<int, WhatsAppMessageTemplate> */
    public Collection $rules;

    public function getTitle(): string
    {
        return 'Mensagens WhatsApp';
    }

    public function mount(WhatsAppMessageTemplateService $templates): void
    {
        $this->rules = $templates->configuredRules();

        $this->form->fill([
            'test_phone' => '',
            'test_event' => WhatsAppMessageEvent::ManualTest->value,
            'rule_event' => null,
            'rule_body' => '',
            'rule_enabled' => true,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $templateService = app(WhatsAppMessageTemplateService::class);

        return $schema->components([
            Section::make('Status da integração')
                ->description('A Evolution API e o disparo global ficam em Integrações API. Aqui você cria regras por evento Hotmart.')
                ->schema([
                    Placeholder::make('integration_status')
                        ->label('Evolution API')
                        ->content(fn (): HtmlString => IntegrationSettings::evolutionConfigured()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configurada.</span>')
                            : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Não configurada — vá em <strong>Integrações API</strong>.</span>')),
                    Placeholder::make('whatsapp_global_status')
                        ->label('Disparo automático global')
                        ->content(fn (): HtmlString => IntegrationSettings::whatsappEnabled()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Ativo — regras ativas abaixo serão disparadas.</span>')
                            : new HtmlString('<span class="text-warning-600 dark:text-warning-400">Desligado — nenhuma regra automática será enviada.</span>')),
                ])
                ->columns(2),
            Section::make('Placeholders disponíveis')
                ->description('Use estes códigos no texto. As mensagens são enviadas em espanhol para o cliente.')
                ->schema([
                    Placeholder::make('placeholder_docs')
                        ->hiddenLabel()
                        ->content(new HtmlString(
                            '<div class="grid gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2 lg:grid-cols-3">'.
                            '<p><code>{nome}</code> — Nome do comprador</p>'.
                            '<p><code>{email}</code> — E-mail de acesso</p>'.
                            '<p><code>{telefone}</code> — Telefone do checkout</p>'.
                            '<p><code>{producto}</code> — Nome do produto Hotmart</p>'.
                            '<p><code>{link_acceso}</code> — URL de login</p>'.
                            '<p><code>{transacao}</code> — Código HP da Hotmart</p>'.
                            '<p><code>{evento}</code> — Evento Hotmart (ex: PURCHASE_APPROVED)</p>'.
                            '<p><code>{moeda}</code> — Moeda da compra</p>'.
                            '<p><code>{valor}</code> — Valor da compra</p>'.
                            '</div>'
                        ))
                        ->columnSpanFull(),
                ])
                ->collapsed(),
            Section::make(fn (): string => $this->editingEvent ? 'Editar regra' : 'Nova regra')
                ->description('Escolha o evento, defina a mensagem e salve. A regra aparecerá na lista abaixo.')
                ->visible(fn (): bool => $this->showRuleForm)
                ->schema([
                    Select::make('rule_event')
                        ->label('Evento Hotmart')
                        ->options(function (WhatsAppMessageTemplateService $templates): array {
                            if ($this->editingEvent) {
                                $event = WhatsAppMessageEvent::from($this->editingEvent);

                                return [$event->value => $event->label()];
                            }

                            return $templates->availableEventOptions();
                        })
                        ->disabled(fn (): bool => filled($this->editingEvent))
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (blank($state) || filled($this->editingEvent)) {
                                return;
                            }

                            $event = WhatsAppMessageEvent::from($state);
                            $set('rule_body', $event->defaultBody());
                            $set('rule_enabled', $event->defaultEnabled());
                        })
                        ->helperText(fn (Get $get): ?string => filled($get('rule_event'))
                            ? WhatsAppMessageEvent::from((string) $get('rule_event'))->description()
                            : 'Selecione o evento que dispara esta mensagem.')
                        ->columnSpanFull(),
                    Placeholder::make('rule_meta')
                        ->label('Condição do sistema')
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->content(function (Get $get): HtmlString {
                            $event = WhatsAppMessageEvent::from((string) $get('rule_event'));

                            return new HtmlString(
                                '<div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">'.
                                '<p><strong>Evento:</strong> <code>'.$event->hotmartEvent().'</code></p>'.
                                '<p><strong>Ação automática:</strong> '.$event->systemAction().'</p>'.
                                '</div>'
                            );
                        })
                        ->columnSpanFull(),
                    Textarea::make('rule_body')
                        ->label('Texto da mensagem (espanhol)')
                        ->rows(6)
                        ->required()
                        ->live(debounce: 500)
                        ->helperText(fn (Get $get): string => filled($get('rule_event'))
                            ? 'Placeholders: '.implode(', ', WhatsAppMessageEvent::from((string) $get('rule_event'))->placeholders())
                            : 'Selecione um evento para ver os placeholders disponíveis.')
                        ->columnSpanFull(),
                    Placeholder::make('rule_preview')
                        ->label('Pré-visualização')
                        ->content(function (Get $get) use ($templateService): HtmlString {
                            $body = (string) ($get('rule_body') ?? '');

                            return new HtmlString(
                                '<div class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">'.
                                e($templateService->preview($body)).
                                '</div>'
                            );
                        })
                        ->columnSpanFull(),
                    Toggle::make('rule_enabled')
                        ->label('Regra ativa')
                        ->helperText('Requer WhatsApp automático ativo em Integrações API.')
                        ->inline(false)
                        ->columnSpanFull(),
                    View::make('filament.pages.manage-messages-rule-form-actions'),
                ]),
            Section::make('Regras de mensagem')
                ->description('Cada regra corresponde a um evento Hotmart. Ative ou desative diretamente na lista.')
                ->afterHeader([
                    Action::make('createRule')
                        ->label('Criar regra')
                        ->icon('heroicon-o-plus')
                        ->visible(fn (): bool => ! $this->showRuleForm)
                        ->action('openCreateRuleForm'),
                ])
                ->schema([
                    View::make('filament.pages.manage-messages-rules-list')
                        ->viewData(fn (): array => [
                            'rules' => $this->rules,
                        ]),
                ]),
            Section::make('Enviar teste')
                ->description('O teste usa o template selecionado e não depende do toggle de disparo automático global.')
                ->schema([
                    Select::make('test_event')
                        ->label('Regra para testar')
                        ->options(collect(WhatsAppMessageEvent::cases())->mapWithKeys(
                            fn (WhatsAppMessageEvent $event): array => [$event->value => $event->label()]
                        ))
                        ->required()
                        ->native(false),
                    TextInput::make('test_phone')
                        ->label('Telefone de teste')
                        ->placeholder('573165247626')
                        ->helperText('Somente dígitos, com código do país. Não é salvo.')
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public function openCreateRuleForm(WhatsAppMessageTemplateService $templates): void
    {
        $available = $templates->availableEventOptions();

        if ($available === []) {
            Notification::make()
                ->title('Todos os eventos já possuem regra')
                ->body('Edite uma regra existente na lista.')
                ->warning()
                ->send();

            return;
        }

        $this->showRuleForm = true;
        $this->editingEvent = null;

        $firstEvent = WhatsAppMessageEvent::from(array_key_first($available));

        $this->data['rule_event'] = $firstEvent->value;
        $this->data['rule_body'] = $firstEvent->defaultBody();
        $this->data['rule_enabled'] = $firstEvent->defaultEnabled();
    }

    public function openEditRuleForm(string $eventValue, WhatsAppMessageTemplateService $templates): void
    {
        $event = WhatsAppMessageEvent::tryFrom($eventValue);

        if (! $event) {
            return;
        }

        $this->showRuleForm = true;
        $this->editingEvent = $event->value;
        $this->data['rule_event'] = $event->value;
        $this->data['rule_body'] = $templates->body($event);
        $this->data['rule_enabled'] = $templates->isEnabled($event);
    }

    public function cancelRuleForm(): void
    {
        $this->showRuleForm = false;
        $this->editingEvent = null;
        $this->data['rule_event'] = null;
        $this->data['rule_body'] = '';
        $this->data['rule_enabled'] = true;
    }

    public function saveRule(WhatsAppMessageTemplateService $templates): void
    {
        $eventValue = $this->data['rule_event'] ?? null;
        $body = trim((string) ($this->data['rule_body'] ?? ''));
        $isEnabled = ! empty($this->data['rule_enabled']);

        if (blank($eventValue)) {
            Notification::make()
                ->title('Selecione um evento Hotmart')
                ->warning()
                ->send();

            return;
        }

        if (blank($body)) {
            Notification::make()
                ->title('Informe o texto da mensagem')
                ->warning()
                ->send();

            return;
        }

        $event = WhatsAppMessageEvent::from($eventValue);
        $templates->upsert($event, $body, $isEnabled);

        $this->refreshRules($templates);
        $this->cancelRuleForm();

        Notification::make()
            ->title('Regra salva')
            ->body($event->label())
            ->success()
            ->send();
    }

    public function toggleRule(string $eventValue, WhatsAppMessageTemplateService $templates): void
    {
        $event = WhatsAppMessageEvent::tryFrom($eventValue);

        if (! $event) {
            return;
        }

        $templates->toggleEnabled($event);
        $this->refreshRules($templates);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Enviar teste')
                ->icon('heroicon-o-paper-airplane')
                ->action(function (): void {
                    if (! IntegrationSettings::evolutionConfigured()) {
                        Notification::make()
                            ->title('Evolution API não configurada')
                            ->body('Configure em Integrações API antes de testar.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $phone = PhoneNumber::normalize($this->data['test_phone'] ?? null);

                    if (blank($phone)) {
                        Notification::make()
                            ->title('Informe um telefone de teste')
                            ->body('Expanda a seção "Enviar teste" e informe o número.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $eventValue = $this->data['test_event'] ?? WhatsAppMessageEvent::ManualTest->value;
                    $event = WhatsAppMessageEvent::tryFrom($eventValue) ?? WhatsAppMessageEvent::ManualTest;
                    $admin = auth()->user();

                    SendWelcomeWhatsAppJob::dispatch(
                        userId: $admin->id,
                        phone: $phone,
                        purchaseId: 0,
                        messageEvent: $event,
                        trigger: $event->dispatchTrigger(),
                        contextHotmartEvent: $event->hotmartEvent(),
                        contextProductTitle: 'Plan Completo — Hotmart',
                        contextCurrency: 'USD',
                        contextAmount: 4.9,
                        contextTransaction: 'HP0000000000',
                    );

                    Notification::make()
                        ->title('Teste enfileirado')
                        ->body("Regra: {$event->label()}. Verifique Disparos WhatsApp.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form'),
            ]);
    }

    private function refreshRules(WhatsAppMessageTemplateService $templates): void
    {
        $this->rules = $templates->configuredRules();
    }
}
