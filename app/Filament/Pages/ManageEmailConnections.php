<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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

class ManageEmailConnections extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'E-mail';

    protected static ?string $navigationLabel = 'Conexões';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Conexões de E-mail';
    }

    public function mount(): void
    {
        $this->form->fill([
            'email_enabled' => IntegrationSettings::emailEnabled(),
            'smtp_host' => IntegrationSettings::smtpHost(),
            'smtp_port' => (string) IntegrationSettings::smtpPort(),
            'smtp_encryption' => IntegrationSettings::smtpEncryption(),
            'smtp_username' => IntegrationSettings::smtpUsername() ?? '',
            'smtp_password' => '',
            'mail_from_address' => IntegrationSettings::mailFromAddress() ?? '',
            'mail_from_name' => IntegrationSettings::mailFromName(),
            'broadcast_rate_per_minute' => (string) IntegrationSettings::broadcastRatePerMinute(),
            'email_logo_url' => Setting::get('email_logo_url', ''),
            'email_logo_path' => filled(Setting::get('email_logo_path'))
                ? [Setting::get('email_logo_path')]
                : [],
            'email_button_color' => IntegrationSettings::emailButtonColor(),
            'email_button_text' => IntegrationSettings::emailButtonText(),
            'email_checkout_button_text' => IntegrationSettings::emailCheckoutButtonText(),
            'email_reply_to' => IntegrationSettings::emailReplyTo() ?? '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status da conexão')
                ->description('Configure o SMTP da Hostinger para disparos automáticos por evento Hotmart.')
                ->schema([
                    Placeholder::make('smtp_status')
                        ->label('SMTP Hostinger')
                        ->content(fn (): HtmlString => IntegrationSettings::emailSmtpConfigured()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configurado — '.e(IntegrationSettings::mailFromAddress()).'</span>')
                            : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Incompleto — informe e-mail, senha e host SMTP.</span>')),
                    Placeholder::make('email_global_status')
                        ->label('Disparo automático global')
                        ->content(fn (): HtmlString => IntegrationSettings::emailEnabled()
                            ? new HtmlString('<span class="text-success-600 dark:text-success-400">Ativo — regras ativas em Disparos serão enviadas.</span>')
                            : new HtmlString('<span class="text-warning-600 dark:text-warning-400">Desligado — nenhum e-mail automático será enviado.</span>')),
                ])
                ->columns(2),
            Section::make('SMTP Hostinger')
                ->description('Use o e-mail completo e a senha da caixa postal Hostinger. Padrão: smtp.hostinger.com na porta 465 (SSL).')
                ->schema([
                    Toggle::make('email_enabled')
                        ->label('Enviar e-mail automático')
                        ->helperText('Ativa o envio nas compras reais (webhook). Os templates ficam em Disparos.')
                        ->inline(false)
                        ->columnSpanFull(),
                    TextInput::make('smtp_host')
                        ->label('Host SMTP')
                        ->default('smtp.hostinger.com')
                        ->required(),
                    Select::make('smtp_port')
                        ->label('Porta')
                        ->options([
                            '465' => '465 (SSL — recomendado Hostinger)',
                            '587' => '587 (TLS / STARTTLS)',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            $set('smtp_encryption', $state === '587' ? 'tls' : 'ssl');
                        }),
                    Select::make('smtp_encryption')
                        ->label('Criptografia')
                        ->options([
                            'ssl' => 'SSL',
                            'tls' => 'TLS',
                        ])
                        ->required()
                        ->native(false),
                    TextInput::make('smtp_username')
                        ->label('E-mail (usuário SMTP)')
                        ->email()
                        ->placeholder('contato@seudominio.com')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('smtp_password')
                        ->label('Senha do e-mail')
                        ->password()
                        ->revealable()
                        ->placeholder('Senha da caixa postal')
                        ->helperText('Se deixar em branco ao salvar, a senha anterior é mantida.')
                        ->columnSpanFull(),
                    TextInput::make('mail_from_address')
                        ->label('Remetente (From)')
                        ->email()
                        ->placeholder('contato@seudominio.com')
                        ->helperText('Normalmente o mesmo e-mail SMTP. Deixe em branco para usar o e-mail acima.')
                        ->columnSpanFull(),
                    TextInput::make('mail_from_name')
                        ->label('Nome do remetente')
                        ->placeholder('Biblioteca Bíblica Digital')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Disparos em massa')
                ->description('Ritmo de envio das campanhas. Quanto mais lento, menor o risco de o SMTP bloquear ou atrasar os envios.')
                ->schema([
                    Select::make('broadcast_rate_per_minute')
                        ->label('Velocidade de envio')
                        ->options([
                            '6' => 'Muy seguro — ~6/min (1 correo cada 10s)',
                            '12' => 'Seguro (recomendado) — ~12/min (1 cada 5s)',
                            '20' => 'Moderado — ~20/min (1 cada 3s)',
                        ])
                        ->default('12')
                        ->required()
                        ->native(false)
                        ->helperText('Os e-mails de cada campanha saem espaçados nesse ritmo, um a um.')
                        ->columnSpanFull(),
                ]),
            Section::make('Aparência dos e-mails')
                ->description('Layout padrão aplicado a todos os disparos. O corpo de cada regra é configurado em Disparos.')
                ->schema([
                    FileUpload::make('email_logo_path')
                        ->label('Upload do logo')
                        ->image()
                        ->disk('public')
                        ->directory('email-settings')
                        ->maxSize(2048)
                        ->helperText('PNG, JPG ou WebP. O upload tem prioridade sobre a URL abaixo.')
                        ->columnSpanFull(),
                    TextInput::make('email_logo_url')
                        ->label('URL do logo (alternativa)')
                        ->url()
                        ->placeholder('https://seudominio.com/storage/logo.png')
                        ->helperText('Use se o logo estiver hospedado fora do site. Ignorado quando há upload acima.')
                        ->columnSpanFull(),
                    TextInput::make('email_button_color')
                        ->label('Cor do botão')
                        ->placeholder('#000000')
                        ->helperText('Cor hexadecimal do botão de acesso (ex.: #000000).'),
                    TextInput::make('email_button_text')
                        ->label('Texto do botão de acesso')
                        ->placeholder('Acceder ahora')
                        ->helperText('Usado quando a regra contém {link_acceso}.'),
                    TextInput::make('email_checkout_button_text')
                        ->label('Texto do botão de checkout')
                        ->placeholder('Completar compra')
                        ->helperText('Usado quando a regra contém {link_checkout}.'),
                    TextInput::make('email_reply_to')
                        ->label('Reply-To (suporte)')
                        ->email()
                        ->placeholder('suporte@seudominio.com')
                        ->helperText('Opcional. Para onde vão as respostas do cliente.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('email_enabled', ! empty($data['email_enabled']) ? '1' : '0');
        Setting::set('smtp_host', $data['smtp_host'] ?? 'smtp.hostinger.com');
        Setting::set('smtp_port', $data['smtp_port'] ?? '465');
        Setting::set('smtp_encryption', $data['smtp_encryption'] ?? 'ssl');
        Setting::set('smtp_username', $data['smtp_username'] ?? '');

        if (filled($data['smtp_password'] ?? null)) {
            Setting::setEncrypted('smtp_password', $data['smtp_password']);
        }

        $fromAddress = filled($data['mail_from_address'] ?? null)
            ? $data['mail_from_address']
            : ($data['smtp_username'] ?? '');

        Setting::set('mail_from_address', $fromAddress);
        Setting::set('mail_from_name', $data['mail_from_name'] ?? config('app.name', 'Biblioteca Bíblica Digital'));
        Setting::set('broadcast_rate_per_minute', $data['broadcast_rate_per_minute'] ?? '12');

        $logoPath = $data['email_logo_path'] ?? [];
        if (is_array($logoPath)) {
            $logoPath = array_values($logoPath)[0] ?? '';
        }
        Setting::set('email_logo_path', $logoPath ?? '');

        Setting::set('email_logo_url', $data['email_logo_url'] ?? '');
        Setting::set('email_button_color', $data['email_button_color'] ?? '#000000');
        Setting::set('email_button_text', $data['email_button_text'] ?? 'Acceder ahora');
        Setting::set('email_checkout_button_text', $data['email_checkout_button_text'] ?? 'Completar compra');
        Setting::set('email_reply_to', $data['email_reply_to'] ?? '');

        Notification::make()
            ->title('Conexão de e-mail salva')
            ->success()
            ->send();

        $this->mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar conexão')
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
