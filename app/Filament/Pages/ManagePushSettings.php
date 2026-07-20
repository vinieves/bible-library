<?php

namespace App\Filament\Pages;

use App\Models\PushSubscription;
use App\Models\Setting;
use App\Services\PushNotificationService;
use App\Services\WebPushService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Minishlink\WebPush\VAPID;
use UnitEnum;

class ManagePushSettings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | UnitEnum | null $navigationGroup = 'Notificações';

    protected static ?string $navigationLabel = 'Configuração de Push';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Configuração de Push (Web Push)';
    }

    public function mount(): void
    {
        $this->form->fill([
            'vapid_subject' => Setting::get('vapid_subject', 'mailto:'.(Setting::get('support_email') ?: 'admin@example.com')),
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
                Section::make('Status')
                    ->schema([
                        Placeholder::make('vapid_status')
                            ->label('Chaves VAPID')
                            ->content(fn (): HtmlString => app(WebPushService::class)->isConfigured()
                                ? new HtmlString('<span class="text-success-600 dark:text-success-400">Configuradas.</span>')
                                : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Não configuradas — clique em <strong>Gerar chaves VAPID</strong>.</span>')),
                        Placeholder::make('subscribers')
                            ->label('Dispositivos inscritos')
                            ->content(fn (): string => (string) PushSubscription::query()->count()),
                        Placeholder::make('public_key')
                            ->label('Chave pública')
                            ->content(fn (): string => Setting::get('vapid_public_key') ?: '—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Identificação (VAPID subject)')
                    ->description('E-mail de contato exigido pelo protocolo (formato mailto:).')
                    ->schema([
                        TextInput::make('vapid_subject')
                            ->label('Subject')
                            ->placeholder('mailto:contato@seudominio.com')
                            ->required(),
                    ]),
                Section::make('Dispositivos inscritos')
                    ->description('Quem ativou as notificações no app.')
                    ->schema([
                        View::make('filament.pages.push-subscribers')
                            ->viewData(fn (): array => [
                                'subscriptions' => PushSubscription::with('user')
                                    ->latest()
                                    ->limit(200)
                                    ->get(),
                                'total' => PushSubscription::query()->count(),
                                'distinctUsers' => PushSubscription::query()
                                    ->whereNotNull('user_id')
                                    ->distinct('user_id')
                                    ->count('user_id'),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('vapid_subject', $data['vapid_subject'] ?? '');

        Notification::make()
            ->title('Configuração salva')
            ->success()
            ->send();
    }

    /**
     * Envia uma notificação de teste para um dispositivo inscrito específico.
     * Chamado pelos botões da tabela de inscritos (wire:click).
     */
    public function sendTestToSubscription(int $subscriptionId): void
    {
        $subscription = PushSubscription::find($subscriptionId);

        if (! $subscription) {
            Notification::make()
                ->title('Inscrição não encontrada')
                ->body('Ela pode ter sido removida. Atualize a página.')
                ->warning()
                ->send();

            return;
        }

        $result = app(WebPushService::class)->sendMany([$subscription], [
            'title' => 'Notificação de teste',
            'body' => 'Push funcionando! 🎉',
            'url' => url('/mi-biblioteca'),
        ]);

        if (($result['sent'] ?? 0) > 0) {
            Notification::make()
                ->title('Teste enviado')
                ->body($subscription->user?->name ?? 'Dispositivo anônimo')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Falha ao enviar')
            ->body('Veja o motivo em storage/logs/laravel.log. Inscrições expiradas são removidas automaticamente.')
            ->danger()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateKeys')
                ->label('Gerar chaves VAPID')
                ->icon('heroicon-o-key')
                ->requiresConfirmation()
                ->modalHeading('Gerar novas chaves VAPID?')
                ->modalDescription('Se já existirem chaves, as inscrições atuais deixarão de funcionar e os usuários precisarão reativar as notificações. Faça isso apenas uma vez.')
                ->visible(fn (): bool => ! app(WebPushService::class)->isConfigured())
                ->action(function (): void {
                    $keys = VAPID::createVapidKeys();

                    Setting::set('vapid_public_key', $keys['publicKey']);
                    Setting::setEncrypted('vapid_private_key', $keys['privateKey']);

                    Notification::make()
                        ->title('Chaves VAPID geradas')
                        ->success()
                        ->send();
                }),
            Action::make('save')
                ->label('Salvar')
                ->action('save'),
            Action::make('sendTest')
                ->label('Enviar teste para mim')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->visible(fn (): bool => app(WebPushService::class)->isConfigured())
                ->action(function (): void {
                    $user = auth()->user();

                    if ($user->pushSubscriptions()->count() === 0) {
                        Notification::make()
                            ->title('Você ainda não tem dispositivos inscritos')
                            ->body('Ative as notificações no app (instalado) com este usuário antes de testar.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = app(PushNotificationService::class)->sendTestToUser($user, [
                        'title' => 'Notificação de teste',
                        'body' => 'Push funcionando! 🎉',
                        'url' => url('/mi-biblioteca'),
                    ]);

                    Notification::make()
                        ->title("Teste enviado ({$result['sent']} ok, {$result['failed']} falhas)")
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
}
