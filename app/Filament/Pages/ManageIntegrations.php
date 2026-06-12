<?php

namespace App\Filament\Pages;

use App\Enums\WebhookPlatform;
use App\Enums\WhatsAppDispatchTrigger;
use App\Jobs\SendWelcomeWhatsAppJob;
use App\Models\Setting;
use App\Services\Webhooks\PhoneNumber;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ManageIntegrations extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-link';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Integrações API';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Integrações API';
    }

    public function mount(): void
    {
        IntegrationSettings::webhookSecret();

        $this->form->fill([
            'webhook_secret' => IntegrationSettings::webhookSecret(),
            'hotmart_hottok' => IntegrationSettings::hotmartHottok(),
            'hotmart_webhook_url' => IntegrationSettings::webhookUrl(WebhookPlatform::Hotmart),
            'generic_webhook_url' => IntegrationSettings::webhookUrl(WebhookPlatform::Generic),
            'whatsapp_enabled' => IntegrationSettings::whatsappEnabled(),
            'evolution_base_url' => Setting::get('evolution_base_url'),
            'evolution_instance' => Setting::get('evolution_instance'),
            'evolution_api_key' => IntegrationSettings::evolutionApiKey(),
            'whatsapp_welcome_template' => IntegrationSettings::whatsappTemplate(),
            'whatsapp_test_phone' => '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhooks de compra')
                    ->description('Configure estas URLs na plataforma de vendas. Apenas vendas aprovadas são processadas.')
                    ->schema([
                        Placeholder::make('webhook_docs')
                            ->label('Autenticação')
                            ->content(new HtmlString(
                                '<div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">'.
                                '<p><strong>Hotmart:</strong> use o <code>hottok</code> do payload ou o header <code>X-HOTMART-HOTTOK</code>. Também é possível enviar o header <code>X-Webhook-Secret</code>.</p>'.
                                '<p><strong>Genérico:</strong> envie o header <code>X-Webhook-Secret</code> com o token abaixo.</p>'.
                                '<p>Em produtos internos, o <code>product_code</code> deve coincidir com o ID, ucode ou offer code da Hotmart.</p>'.
                                '</div>'
                            )),
                        TextInput::make('hotmart_webhook_url')
                            ->label('URL webhook Hotmart')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable(copyMessage: 'URL copiada')
                            ->columnSpanFull(),
                        TextInput::make('generic_webhook_url')
                            ->label('URL webhook genérico')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable(copyMessage: 'URL copiada')
                            ->columnSpanFull(),
                        TextInput::make('webhook_secret')
                            ->label('Token X-Webhook-Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Usado por integrações genéricas e como reserva de segurança.')
                            ->columnSpanFull(),
                        TextInput::make('hotmart_hottok')
                            ->label('Hotmart hottok')
                            ->password()
                            ->revealable()
                            ->helperText('Cole novamente ao salvar. Se deixar em branco, o valor anterior é mantido.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Evolution API (WhatsApp)')
                    ->description('Mensagem automática ao aprovar uma compra. Placeholders: {nome}, {email}, {telefone}, {producto}, {link_acceso}. O texto da mensagem vai em espanhol para o cliente.')
                    ->schema([
                        Toggle::make('whatsapp_enabled')
                            ->label('Enviar WhatsApp automático')
                            ->helperText('Só afeta compras reais (webhook). O botão de teste funciona com o toggle desligado.')
                            ->inline(false),
                        TextInput::make('evolution_base_url')
                            ->label('URL base Evolution API')
                            ->placeholder('https://api.seudominio.com')
                            ->url(),
                        TextInput::make('evolution_instance')
                            ->label('Nome da instância')
                            ->placeholder('biblioteca'),
                        TextInput::make('evolution_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable(),
                        Textarea::make('whatsapp_welcome_template')
                            ->label('Mensagem de boas-vindas (espanhol — enviada ao cliente)')
                            ->rows(6)
                            ->columnSpanFull(),
                        TextInput::make('whatsapp_test_phone')
                            ->label('Telefone de teste')
                            ->placeholder('5511999999999')
                            ->helperText('Somente dígitos, com código do país. Não é salvo.')
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (filled($data['webhook_secret'] ?? null)) {
            Setting::setEncrypted('webhook_secret', $data['webhook_secret']);
        }

        if (filled($data['hotmart_hottok'] ?? null)) {
            Setting::setEncrypted('hotmart_hottok', $data['hotmart_hottok']);
        }

        Setting::set('whatsapp_enabled', ! empty($data['whatsapp_enabled']) ? '1' : '0');
        Setting::set('evolution_base_url', $data['evolution_base_url'] ?? '');
        Setting::set('evolution_instance', $data['evolution_instance'] ?? '');

        if (filled($data['evolution_api_key'] ?? null)) {
            Setting::setEncrypted('evolution_api_key', $data['evolution_api_key']);
        }

        Setting::set('whatsapp_welcome_template', $data['whatsapp_welcome_template'] ?? '');

        Notification::make()
            ->title('Integrações salvas')
            ->success()
            ->send();

        $this->mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateSecret')
                ->label('Regenerar token')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerar token do webhook')
                ->modalDescription('Integrações que usarem o token anterior deixarão de funcionar até serem atualizadas.')
                ->action(function (): void {
                    IntegrationSettings::regenerateWebhookSecret();

                    Notification::make()
                        ->title('Token regenerado')
                        ->success()
                        ->send();

                    $this->mount();
                }),
            Action::make('sendTestWhatsApp')
                ->label('Enviar teste WhatsApp')
                ->action(function (): void {
                    // Campo com dehydrated(false) não entra em getState() — ler de $this->data
                    $phone = PhoneNumber::normalize($this->data['whatsapp_test_phone'] ?? null);

                    if (blank($phone)) {
                        Notification::make()
                            ->title('Informe um telefone de teste')
                            ->warning()
                            ->send();

                        return;
                    }

                    $formState = $this->form->getState();

                    if (
                        blank($formState['evolution_base_url'] ?? null)
                        || blank($formState['evolution_instance'] ?? null)
                        || blank($formState['evolution_api_key'] ?? null)
                    ) {
                        Notification::make()
                            ->title('Preencha URL, instância e API Key da Evolution')
                            ->body('Clique em Salvar integrações antes de testar.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Setting::set('evolution_base_url', $formState['evolution_base_url'] ?? '');
                    Setting::set('evolution_instance', $formState['evolution_instance'] ?? '');
                    Setting::setEncrypted('evolution_api_key', $formState['evolution_api_key'] ?? '');

                    $admin = auth()->user();

                    SendWelcomeWhatsAppJob::dispatch(
                        userId: $admin->id,
                        phone: $phone,
                        purchaseId: 0,
                        trigger: WhatsAppDispatchTrigger::ManualTest,
                    );

                    Notification::make()
                        ->title('Teste enfileirado')
                        ->body('Verifique a fila e os logs se a mensagem não chegar.')
                        ->success()
                        ->send();
                }),
            Action::make('save')
                ->label('Salvar integrações')
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
}
