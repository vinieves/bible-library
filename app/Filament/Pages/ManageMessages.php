<?php

namespace App\Filament\Pages;

use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendWelcomeWhatsAppJob;
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
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ManageMessages extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Mensagens';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Mensagens WhatsApp';
    }

    public function mount(WhatsAppMessageTemplateService $templates): void
    {
        $state = [
            'test_phone' => '',
            'test_event' => WhatsAppMessageEvent::ManualTest->value,
        ];

        foreach (WhatsAppMessageEvent::cases() as $event) {
            $state[$this->fieldKey($event, 'enabled')] = $templates->isEnabled($event);
            $state[$this->fieldKey($event, 'body')] = $templates->body($event);
        }

        $this->form->fill($state);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $templateService = app(WhatsAppMessageTemplateService::class);

        return $schema
            ->components([
                Section::make('Status da integração')
                    ->description('A conexão com a Evolution API é configurada em Integrações API.')
                    ->schema([
                        Placeholder::make('integration_status')
                            ->label('Evolution API')
                            ->content(fn (): HtmlString => IntegrationSettings::evolutionConfigured()
                                ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configurada.</span>')
                                : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Não configurada — vá em <strong>Integrações API</strong> e preencha URL, instância e API Key.</span>')),
                        Placeholder::make('whatsapp_global_status')
                            ->label('Disparo automático')
                            ->content(fn (): HtmlString => IntegrationSettings::whatsappEnabled()
                                ? new HtmlString('<span class="text-success-600 dark:text-success-400">Ativo para compras reais (webhook).</span>')
                                : new HtmlString('<span class="text-warning-600 dark:text-warning-400">Desligado — ative em <strong>Integrações API</strong> para enviar nas compras.</span>')),
                    ])
                    ->columns(2),
                Section::make('Placeholders disponíveis')
                    ->description('Use estes códigos no texto. A mensagem final é enviada em espanhol para o cliente.')
                    ->schema([
                        Placeholder::make('placeholder_docs')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<div class="grid gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">'.
                                '<p><code>{nome}</code> — Nome do comprador</p>'.
                                '<p><code>{email}</code> — E-mail de acesso</p>'.
                                '<p><code>{telefone}</code> — Telefone do checkout</p>'.
                                '<p><code>{producto}</code> — Nome do produto interno</p>'.
                                '<p><code>{link_acceso}</code> — URL de login da biblioteca</p>'.
                                '<p><code>{transacao}</code> — Código da transação Hotmart</p>'.
                                '</div>'
                            ))
                            ->columnSpanFull(),
                    ]),
                ...collect(WhatsAppMessageEvent::cases())
                    ->map(fn (WhatsAppMessageEvent $event): Section => $this->eventSection($event, $templateService))
                    ->all(),
                Section::make('Enviar teste')
                    ->description('O teste usa o template selecionado e não depende do toggle de disparo automático.')
                    ->schema([
                        Select::make('test_event')
                            ->label('Evento para testar')
                            ->options(collect(WhatsAppMessageEvent::cases())->mapWithKeys(
                                fn (WhatsAppMessageEvent $event) => [$event->value => $event->label()]
                            ))
                            ->required()
                            ->native(false),
                        TextInput::make('test_phone')
                            ->label('Telefone de teste')
                            ->placeholder('5215512345678')
                            ->helperText('Somente dígitos, com código do país. Não é salvo.')
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    private function eventSection(WhatsAppMessageEvent $event, WhatsAppMessageTemplateService $templateService): Section
    {
        $bodyKey = $this->fieldKey($event, 'body');
        $enabledKey = $this->fieldKey($event, 'enabled');

        return Section::make($event->label())
            ->description($event->description())
            ->schema([
                Placeholder::make("hotmart_events_{$event->value}")
                    ->label('Eventos Hotmart relacionados')
                    ->content($event->hotmartEvents())
                    ->columnSpanFull(),
                Toggle::make($enabledKey)
                    ->label('Enviar automaticamente')
                    ->helperText($event === WhatsAppMessageEvent::ManualTest
                        ? 'Não afeta o botão de teste abaixo.'
                        : 'Só dispara em compras reais quando o WhatsApp automático está ativo em Integrações API.')
                    ->inline(false)
                    ->columnSpanFull(),
                Textarea::make($bodyKey)
                    ->label('Texto da mensagem (espanhol)')
                    ->rows(6)
                    ->required()
                    ->live(onBlur: true)
                    ->helperText('Placeholders: '.implode(', ', $event->placeholders()))
                    ->columnSpanFull(),
                Placeholder::make("preview_{$event->value}")
                    ->label('Pré-visualização')
                    ->content(function (Get $get) use ($bodyKey, $templateService): HtmlString {
                        $body = (string) ($get($bodyKey) ?? '');

                        return new HtmlString(
                            '<div class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">'.
                            e($templateService->preview($body)).
                            '</div>'
                        );
                    })
                    ->columnSpanFull(),
            ]);
    }

    public function save(WhatsAppMessageTemplateService $templates): void
    {
        $data = $this->form->getState();

        foreach (WhatsAppMessageEvent::cases() as $event) {
            $templates->upsert(
                event: $event,
                body: (string) ($data[$this->fieldKey($event, 'body')] ?? $event->defaultBody()),
                isEnabled: ! empty($data[$this->fieldKey($event, 'enabled')]),
            );
        }

        Notification::make()
            ->title('Mensagens salvas')
            ->success()
            ->send();

        $this->mount($templates);
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
                    );

                    Notification::make()
                        ->title('Teste enfileirado')
                        ->body("Template: {$event->label()}. Verifique Disparos WhatsApp.")
                        ->success()
                        ->send();
                }),
            Action::make('save')
                ->label('Salvar mensagens')
                ->action('save'),
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

    private function fieldKey(WhatsAppMessageEvent $event, string $suffix): string
    {
        return "{$event->value}_{$suffix}";
    }
}
