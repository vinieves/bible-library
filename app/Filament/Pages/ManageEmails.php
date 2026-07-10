<?php

namespace App\Filament\Pages;

use App\Enums\EmailDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailMessageTemplate;
use App\Services\EmailMessageTemplateService;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Str;
use UnitEnum;

class ManageEmails extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string | UnitEnum | null $navigationGroup = 'E-mail';

    protected static ?string $navigationLabel = 'Disparos';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public bool $showRuleForm = false;

    public ?string $editingEvent = null;

    /** @var Collection<int, EmailMessageTemplate> */
    public Collection $rules;

    public function getTitle(): string
    {
        return 'Disparos de E-mail';
    }

    public function mount(EmailMessageTemplateService $templates): void
    {
        $this->rules = $templates->configuredRules();

        $this->form->fill([
            'test_email' => '',
            'test_event' => $this->defaultTestEvent($templates),
            'rule_event' => null,
            'rule_subject' => '',
            'rule_body' => '',
            'rule_inline_images' => [],
            'rule_attachments' => [],
            'rule_enabled' => true,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $templateService = app(EmailMessageTemplateService::class);

        return $schema->components([
            Section::make('Status da integração')
                ->description('O SMTP Hostinger e o disparo global ficam em Conexões. Aqui você cria regras por status de venda Hotmart.')
                ->schema([
                    Placeholder::make('smtp_status')
                        ->label('SMTP Hostinger')
                        ->content(fn (): HtmlString => IntegrationSettings::emailSmtpConfigured()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configurado — '.e(IntegrationSettings::mailFromAddress()).'</span>')
                            : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Não configurado — vá em <strong>Conexões</strong>.</span>')),
                    Placeholder::make('email_global_status')
                        ->label('Disparo automático global')
                        ->content(fn (): HtmlString => IntegrationSettings::emailEnabled()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Ativo — regras ativas abaixo serão disparadas.</span>')
                            : new HtmlString('<span class="text-warning-600 dark:text-warning-400">Desligado — nenhuma regra automática será enviada.</span>')),
                ])
                ->columns(2),
            Section::make('Placeholders disponíveis')
                ->description('Use estes códigos no assunto e no corpo. Os e-mails são enviados em espanhol para o cliente.')
                ->schema([
                    Placeholder::make('placeholder_docs')
                        ->hiddenLabel()
                        ->content(new HtmlString(
                            '<div class="grid gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2 lg:grid-cols-3">'.
                            '<p><code>{nome}</code> — Nome do comprador</p>'.
                            '<p><code>{email}</code> — E-mail de acesso</p>'.
                            '<p><code>{telefone}</code> — Telefone do checkout</p>'.
                            '<p><code>{producto}</code> — Nome do produto Hotmart</p>'.
                            '<p><code>{link_acceso}</code> — Botão de acesso à biblioteca</p>'.
                            '<p><code>{link_checkout}</code> — Botão de checkout (carrinho abandonado)</p>'.
                            '<p><code>{imagen:nome}</code> — Imagem enviada abaixo (ex.: guia.png → <code>{imagen:guia}</code>)</p>'.
                            '<p><code>{transacao}</code> — Código HP da Hotmart</p>'.
                            '<p><code>{evento}</code> — Evento Hotmart (ex: PURCHASE_APPROVED)</p>'.
                            '<p><code>{moeda}</code> — Moeda da compra</p>'.
                            '<p><code>{valor}</code> — Valor da compra</p>'.
                            '</div>'
                        ))
                        ->columnSpanFull(),
                ])
                ->collapsed(),
            Section::make(fn (): string => $this->editingEvent ? 'Editar regra de e-mail' : 'Nova regra de e-mail')
                ->description('Escolha a condição (status da venda), defina assunto e corpo, e salve.')
                ->visible(fn (): bool => $this->showRuleForm)
                ->schema([
                    Select::make('rule_event')
                        ->label('Condição (status da venda)')
                        ->options(function (EmailMessageTemplateService $templates): array {
                            if ($this->editingEvent) {
                                $event = WhatsAppMessageEvent::from($this->editingEvent);

                                return [$event->group() => [$event->value => $event->conditionLabel()]];
                            }

                            return $templates->availableConditionOptions();
                        })
                        ->disabled(fn (): bool => filled($this->editingEvent))
                        ->required()
                        ->native(false)
                        ->live()
                        ->placeholder('Selecione um status da venda')
                        ->afterStateUpdated(function (?string $state, Set $set, EmailMessageTemplateService $templates): void {
                            if (blank($state) || filled($this->editingEvent)) {
                                return;
                            }

                            $event = WhatsAppMessageEvent::from($state);
                            $set('rule_subject', $templates->subject($event));
                            $set('rule_body', $event->defaultBody());
                            $set('rule_enabled', true);
                        })
                        ->helperText(fn (Get $get): ?string => filled($get('rule_event'))
                            ? WhatsAppMessageEvent::from((string) $get('rule_event'))->description()
                            : 'Escolha quando o e-mail deve ser enviado.')
                        ->columnSpanFull(),
                    Placeholder::make('rule_meta')
                        ->label('O que o sistema faz')
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->content(function (Get $get): HtmlString {
                            $event = WhatsAppMessageEvent::from((string) $get('rule_event'));

                            return new HtmlString(
                                '<p class="text-sm text-gray-600 dark:text-gray-300">'.
                                '<strong>Evento Hotmart:</strong> <code>'.$event->hotmartEvent().'</code> · '.
                                '<strong>Ação:</strong> '.$event->systemAction().
                                '</p>'
                            );
                        })
                        ->columnSpanFull(),
                    TextInput::make('rule_subject')
                        ->label('Assunto do e-mail')
                        ->required()
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->live(debounce: 500)
                        ->columnSpanFull(),
                    Textarea::make('rule_body')
                        ->label('Corpo do e-mail (espanhol)')
                        ->rows(8)
                        ->required()
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->live(debounce: 500)
                        ->helperText(fn (Get $get): string => filled($get('rule_event'))
                            ? 'Placeholders: '.implode(', ', WhatsAppMessageEvent::from((string) $get('rule_event'))->placeholders()).', {imagen:nome}'
                            : 'Selecione uma condição para editar o e-mail.')
                        ->columnSpanFull(),
                    FileUpload::make('rule_inline_images')
                        ->label('Imagens no corpo do e-mail')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('email-rules/inline')
                        ->maxSize(3072)
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->live()
                        ->helperText('PNG, JPG ou WebP. Cole no texto acima o código gerado abaixo para cada imagem.')
                        ->columnSpanFull(),
                    Placeholder::make('rule_inline_image_codes')
                        ->label('Códigos das imagens')
                        ->visible(fn (Get $get): bool => filled($get('rule_event')) && filled($get('rule_inline_images')))
                        ->content(function (Get $get, EmailMessageTemplateService $templates): HtmlString {
                            $paths = $get('rule_inline_images') ?? [];
                            $map = $templates->inlineImagesFromPaths(is_array($paths) ? $paths : []);

                            if ($map === []) {
                                return new HtmlString('<p class="text-sm text-gray-400">Envie imagens para ver os códigos.</p>');
                            }

                            $lines = collect($map)
                                ->keys()
                                ->map(fn (string $slug): string => '<code>{imagen:'.$slug.'}</code>')
                                ->implode(' · ');

                            return new HtmlString('<p class="text-sm text-gray-300">'.$lines.'</p>');
                        })
                        ->columnSpanFull(),
                    FileUpload::make('rule_attachments')
                        ->label('Anexos do e-mail')
                        ->multiple()
                        ->disk('public')
                        ->directory('email-rules/attachments')
                        ->maxSize(10240)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->helperText('PDF, imagens ou Word enviados como anexo junto ao e-mail (máx. 10 MB por arquivo).')
                        ->columnSpanFull(),
                    Placeholder::make('rule_preview')
                        ->label('Pré-visualização')
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->content(function (Get $get) use ($templateService): HtmlString {
                            $subject = (string) ($get('rule_subject') ?? '');
                            $body = (string) ($get('rule_body') ?? '');

                            if (blank($body)) {
                                return new HtmlString('<p class="text-sm text-gray-400">Escreva o corpo para ver a prévia.</p>');
                            }

                            $inlineImages = $templateService->inlineImagesFromPaths(
                                is_array($get('rule_inline_images')) ? $get('rule_inline_images') : [],
                            );

                            return new HtmlString(
                                '<div class="space-y-3">'.
                                '<p class="text-sm font-medium text-gray-200">Assunto: '.e($templateService->preview($subject)).'</p>'.
                                '<div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">'.
                                $templateService->previewHtml($body, $inlineImages).
                                '</div></div>'
                            );
                        })
                        ->columnSpanFull(),
                    Toggle::make('rule_enabled')
                        ->label('Regra ativa')
                        ->visible(fn (Get $get): bool => filled($get('rule_event')))
                        ->helperText('Requer e-mail automático ativo em Conexões.')
                        ->inline(false)
                        ->columnSpanFull(),
                    View::make('filament.pages.manage-emails-rule-form-actions')
                        ->visible(fn (Get $get): bool => filled($get('rule_event'))),
                ]),
            Section::make('Regras de e-mail')
                ->description($this->rules->isEmpty()
                    ? 'Nenhuma regra criada ainda. Clique em Criar regra para começar.'
                    : 'Suas regras salvas. Ative ou desative direto na lista.')
                ->afterHeader([
                    Action::make('createRule')
                        ->label('Criar regra')
                        ->icon('heroicon-o-plus')
                        ->visible(fn (): bool => ! $this->showRuleForm)
                        ->action('openCreateRuleForm'),
                ])
                ->schema([
                    View::make('filament.pages.manage-emails-rules-list')
                        ->viewData(fn (): array => [
                            'rules' => $this->rules,
                        ]),
                ]),
            Section::make('Enviar teste')
                ->description('Testa uma regra salva. Não depende do toggle de disparo automático global.')
                ->schema([
                    Select::make('test_event')
                        ->label('Regra para testar')
                        ->options(fn (EmailMessageTemplateService $templates): array => $this->testEventOptions($templates))
                        ->required()
                        ->native(false)
                        ->disabled(fn (EmailMessageTemplateService $templates): bool => $templates->configuredRules()->isEmpty()),
                    TextInput::make('test_email')
                        ->label('E-mail de teste')
                        ->email()
                        ->placeholder('seu@email.com')
                        ->helperText('Não é salvo.')
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public function openCreateRuleForm(EmailMessageTemplateService $templates): void
    {
        if ($templates->availableConditionOptions() === []) {
            Notification::make()
                ->title('Todos os status já possuem regra')
                ->body('Edite ou exclua uma regra existente para criar outra.')
                ->warning()
                ->send();

            return;
        }

        $this->showRuleForm = true;
        $this->editingEvent = null;
        $this->data['rule_event'] = null;
        $this->data['rule_subject'] = '';
        $this->data['rule_body'] = '';
        $this->data['rule_inline_images'] = [];
        $this->data['rule_attachments'] = [];
        $this->data['rule_enabled'] = true;
    }

    public function openEditRuleForm(string $eventValue, EmailMessageTemplateService $templates): void
    {
        $event = WhatsAppMessageEvent::tryFrom($eventValue);

        if (! $event) {
            return;
        }

        $template = EmailMessageTemplate::query()
            ->where('event', $event->value)
            ->first();

        $this->showRuleForm = true;
        $this->editingEvent = $event->value;
        $this->data['rule_event'] = $event->value;
        $this->data['rule_subject'] = $templates->subject($event);
        $this->data['rule_body'] = $templates->body($event);
        $this->data['rule_inline_images'] = array_values($template?->inline_images ?? []);
        $this->data['rule_attachments'] = $template?->attachments ?? [];
        $this->data['rule_enabled'] = $templates->isEnabled($event);
    }

    public function cancelRuleForm(): void
    {
        $this->showRuleForm = false;
        $this->editingEvent = null;
        $this->data['rule_event'] = null;
        $this->data['rule_subject'] = '';
        $this->data['rule_body'] = '';
        $this->data['rule_inline_images'] = [];
        $this->data['rule_attachments'] = [];
        $this->data['rule_enabled'] = true;
    }

    public function saveRule(EmailMessageTemplateService $templates): void
    {
        $eventValue = $this->data['rule_event'] ?? null;
        $subject = trim((string) ($this->data['rule_subject'] ?? ''));
        $body = trim((string) ($this->data['rule_body'] ?? ''));
        $isEnabled = ! empty($this->data['rule_enabled']);
        $inlineImages = $templates->inlineImagesFromPaths($this->data['rule_inline_images'] ?? []);
        $attachments = $templates->normalizeAttachmentPaths($this->data['rule_attachments'] ?? []);

        if (blank($eventValue)) {
            Notification::make()
                ->title('Selecione uma condição')
                ->warning()
                ->send();

            return;
        }

        if (blank($subject) || blank($body)) {
            Notification::make()
                ->title('Informe assunto e corpo do e-mail')
                ->warning()
                ->send();

            return;
        }

        $event = WhatsAppMessageEvent::from($eventValue);
        $templates->upsert($event, $subject, $body, $isEnabled, $inlineImages, $attachments);

        $this->refreshRules($templates);
        $this->cancelRuleForm();
        $this->data['test_event'] = $this->defaultTestEvent($templates);

        Notification::make()
            ->title('Regra salva')
            ->body($event->conditionLabel())
            ->success()
            ->send();
    }

    public function toggleRule(string $eventValue, EmailMessageTemplateService $templates): void
    {
        $event = WhatsAppMessageEvent::tryFrom($eventValue);

        if (! $event) {
            return;
        }

        $templates->toggleEnabled($event);
        $this->refreshRules($templates);
    }

    public function deleteRule(string $eventValue, EmailMessageTemplateService $templates): void
    {
        $event = WhatsAppMessageEvent::tryFrom($eventValue);

        if (! $event) {
            return;
        }

        if ($this->editingEvent === $event->value) {
            $this->cancelRuleForm();
        }

        $templates->deleteRule($event);
        $this->refreshRules($templates);
        $this->data['test_event'] = $this->defaultTestEvent($templates);

        Notification::make()
            ->title('Regra excluída')
            ->body($event->conditionLabel())
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Enviar teste')
                ->icon('heroicon-o-paper-airplane')
                ->action(function (EmailMessageTemplateService $templates): void {
                    if (! IntegrationSettings::emailSmtpConfigured()) {
                        Notification::make()
                            ->title('SMTP não configurado')
                            ->body('Configure em Conexões antes de testar.')
                            ->warning()
                            ->send();

                        return;
                    }

                    if ($templates->configuredRules()->isEmpty()) {
                        Notification::make()
                            ->title('Crie uma regra antes de testar')
                            ->warning()
                            ->send();

                        return;
                    }

                    $testEmail = trim((string) ($this->data['test_email'] ?? ''));

                    if (blank($testEmail) || ! filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                        Notification::make()
                            ->title('Informe um e-mail de teste válido')
                            ->body('Expanda a seção "Enviar teste" e informe o endereço.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $eventValue = $this->data['test_event'] ?? null;
                    $event = WhatsAppMessageEvent::tryFrom((string) $eventValue);

                    if (! $event || ! $templates->configuredRules()->contains(fn (EmailMessageTemplate $rule): bool => $rule->event === $event)) {
                        Notification::make()
                            ->title('Selecione uma regra válida para testar')
                            ->warning()
                            ->send();

                        return;
                    }

                    $admin = auth()->user();

                    SendTransactionalEmailJob::dispatch(
                        userId: $admin->id,
                        recipientEmail: $testEmail,
                        purchaseId: 0,
                        messageEvent: $event,
                        trigger: EmailDispatchTrigger::ManualTest,
                        contextHotmartEvent: $event->hotmartEvent(),
                        contextProductTitle: 'Plan Completo — Hotmart',
                        contextCurrency: 'USD',
                        contextAmount: 4.9,
                        contextTransaction: 'HP-TEST-'.Str::uuid(),
                    );

                    Notification::make()
                        ->title('Teste enfileirado')
                        ->body("Regra: {$event->conditionLabel()}. Verifique Histórico de e-mail.")
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

    private function refreshRules(EmailMessageTemplateService $templates): void
    {
        $this->rules = $templates->configuredRules();
    }

    private function defaultTestEvent(EmailMessageTemplateService $templates): ?string
    {
        return $templates->configuredRules()->first()?->event->value;
    }

    /**
     * @return array<string, string>
     */
    private function testEventOptions(EmailMessageTemplateService $templates): array
    {
        return $templates->configuredRules()
            ->mapWithKeys(fn (EmailMessageTemplate $rule): array => [
                $rule->event->value => $rule->event->conditionLabel(),
            ])
            ->all();
    }
}
