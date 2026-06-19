<?php

namespace App\Filament\Pages;

use App\Enums\WebhookPlatform;
use App\Models\Setting;
use App\Support\EvolutionInstanceOptions;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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
            'hotmart_webhook_url' => IntegrationSettings::webhookUrl(WebhookPlatform::Hotmart),
            'generic_webhook_url' => IntegrationSettings::webhookUrl(WebhookPlatform::Generic),
            'whatsapp_enabled' => IntegrationSettings::whatsappEnabled(),
            'evolution_base_url' => Setting::get('evolution_base_url'),
            'evolution_instance_messages' => Setting::get('evolution_instance_messages') ?: Setting::get('evolution_instance'),
            'evolution_instance_flows' => Setting::get('evolution_instance_flows') ?: Setting::get('evolution_instance'),
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
                        Placeholder::make('hotmart_hottok_status')
                            ->label('Status do hottok')
                            ->content(fn (): HtmlString => filled(IntegrationSettings::hotmartHottok())
                                ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configurado no servidor.</span>')
                                : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Não configurado — cole o hottok da Hotmart abaixo e salve.</span>'))
                            ->columnSpanFull(),
                        TextInput::make('hotmart_hottok')
                            ->label('Hotmart hottok')
                            ->password()
                            ->revealable()
                            ->placeholder('Cole o hottok da Hotmart aqui')
                            ->helperText('O campo fica vazio por segurança. Se deixar em branco ao salvar, o valor anterior é mantido.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Evolution API (WhatsApp)')
                    ->description('Conexão com a Evolution API. Os textos de cada evento são configurados em Mensagens.')
                    ->schema([
                        Toggle::make('whatsapp_enabled')
                            ->label('Enviar WhatsApp automático')
                            ->helperText('Ativa o envio nas compras reais (webhook). Os templates ficam em Mensagens.')
                            ->inline(false),
                        TextInput::make('evolution_base_url')
                            ->label('URL base Evolution API')
                            ->placeholder('https://wpp.seudominio.com')
                            ->url(),
                        Select::make('evolution_instance_messages')
                            ->label('Instância — Mensagens Hotmart')
                            ->options(fn (): array => EvolutionInstanceOptions::selectOptions(
                                Setting::get('evolution_instance_messages'),
                                Setting::get('evolution_instance'),
                            ))
                            ->searchable()
                            ->native(false)
                            ->placeholder('Selecione a instância')
                            ->helperText('Usada nos disparos automáticos de venda e no botão Enviar teste em Mensagens.'),
                        Select::make('evolution_instance_flows')
                            ->label('Instância padrão — Fluxos')
                            ->options(fn (): array => EvolutionInstanceOptions::selectOptions(
                                Setting::get('evolution_instance_flows'),
                                Setting::get('evolution_instance'),
                            ))
                            ->searchable()
                            ->native(false)
                            ->placeholder('Selecione a instância')
                            ->helperText('Padrão para fluxos sem instância própria. Cada fluxo pode sobrescrever em Fluxos.'),
                        TextInput::make('evolution_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Se deixar em branco ao salvar, o valor anterior é mantido.'),
                        TextInput::make('evolution_webhook_url')
                            ->label('URL webhook Evolution (fluxos / primeira mensagem)')
                            ->default(fn (): string => IntegrationSettings::evolutionWebhookUrl())
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable(copyMessage: 'URL copiada')
                            ->helperText('Evento MESSAGES_UPSERT. Registre em Fluxos → editar fluxo de primeira mensagem.')
                            ->columnSpanFull(),
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
        Setting::set('evolution_instance_messages', $data['evolution_instance_messages'] ?? '');
        Setting::set('evolution_instance_flows', $data['evolution_instance_flows'] ?? '');
        Setting::set('evolution_instance', $data['evolution_instance_messages'] ?? '');

        if (filled($data['evolution_api_key'] ?? null)) {
            Setting::setEncrypted('evolution_api_key', $data['evolution_api_key']);
        }

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
