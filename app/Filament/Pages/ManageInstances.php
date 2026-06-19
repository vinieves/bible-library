<?php

namespace App\Filament\Pages;

use App\DataTransferObjects\EvolutionInstanceSummary;
use App\Models\Setting;
use App\Services\EvolutionInstanceService;
use App\Support\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use UnitEnum;

class ManageInstances extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Instâncias';

    protected static ?int $navigationSort = 6;

    /** @var list<array{name: string, state: string, instanceId: ?string, profileName: ?string, ownerJid: ?string, stateLabel: string, stateColor: string}> */
    public array $instances = [];

    public ?string $activeInstance = null;

    public ?string $qrInstance = null;

    public ?string $qrBase64 = null;

    public ?string $qrCode = null;

    public ?string $qrPairingCode = null;

    public ?array $createData = [];

    public function getTitle(): string
    {
        return 'Instâncias WhatsApp';
    }

    public function mount(EvolutionInstanceService $instances): void
    {
        $this->activeInstance = IntegrationSettings::evolutionInstance();
        $this->createData = ['instance_name' => ''];

        $this->loadInstances($instances);
    }

    public function loadInstances(EvolutionInstanceService $instances): void
    {
        if (! IntegrationSettings::evolutionApiReady()) {
            $this->instances = [];

            return;
        }

        try {
            $this->instances = $this->serializeInstances($instances->fetchAll());
            $this->activeInstance = IntegrationSettings::evolutionInstance();
        } catch (\Throwable $exception) {
            $this->instances = [];

            Notification::make()
                ->title('Não foi possível listar instâncias')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    public function refreshInstances(EvolutionInstanceService $instances): void
    {
        $this->loadInstances($instances);

        Notification::make()
            ->title('Lista atualizada')
            ->success()
            ->send();
    }

    public function connectInstance(string $instanceName, EvolutionInstanceService $instances): void
    {
        try {
            $result = $instances->connect($instanceName);

            $this->qrInstance = $instanceName;
            $this->qrBase64 = $result['base64'];
            $this->qrCode = $result['code'];
            $this->qrPairingCode = $result['pairingCode'];

            if (blank($this->qrBase64)) {
                Notification::make()
                    ->title('Conexão iniciada')
                    ->body('QR Code não retornado. A instância pode já estar conectada.')
                    ->warning()
                    ->send();
            }

            $this->loadInstances($instances);
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Falha ao conectar')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    public function closeQrModal(): void
    {
        $this->qrInstance = null;
        $this->qrBase64 = null;
        $this->qrCode = null;
        $this->qrPairingCode = null;
    }

    public function setActiveInstance(string $instanceName): void
    {
        Setting::set('evolution_instance', $instanceName);
        $this->activeInstance = $instanceName;

        Notification::make()
            ->title('Instância ativa definida')
            ->body("Mensagens e fluxos usarão: {$instanceName}")
            ->success()
            ->send();
    }

    public function logoutInstance(string $instanceName, EvolutionInstanceService $instances): void
    {
        try {
            $instances->logout($instanceName);
            $this->loadInstances($instances);

            Notification::make()
                ->title('Instância desconectada')
                ->body($instanceName)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Falha ao desconectar')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    public function restartInstance(string $instanceName, EvolutionInstanceService $instances): void
    {
        try {
            $instances->restart($instanceName);
            $this->loadInstances($instances);

            Notification::make()
                ->title('Instância reiniciada')
                ->body($instanceName)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Falha ao reiniciar')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    public function deleteInstance(string $instanceName, EvolutionInstanceService $instances): void
    {
        try {
            $instances->delete($instanceName);

            if ($this->activeInstance === $instanceName) {
                Setting::set('evolution_instance', '');
                $this->activeInstance = null;
            }

            if ($this->qrInstance === $instanceName) {
                $this->closeQrModal();
            }

            $this->loadInstances($instances);

            Notification::make()
                ->title('Instância excluída')
                ->body($instanceName)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Falha ao excluir')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    public function createInstance(EvolutionInstanceService $instances): void
    {
        $name = Str::slug(trim((string) ($this->createData['instance_name'] ?? '')), '_');

        if (blank($name)) {
            Notification::make()
                ->title('Informe o nome da instância')
                ->warning()
                ->send();

            return;
        }

        try {
            $result = $instances->create($name);

            if (filled($result['base64'])) {
                $this->qrInstance = $result['instanceName'];
                $this->qrBase64 = $result['base64'];
                $this->qrCode = $result['code'];
                $this->qrPairingCode = $result['pairingCode'];
            }

            $this->createData['instance_name'] = '';
            $this->loadInstances($instances);

            if (blank($this->activeInstance)) {
                Setting::set('evolution_instance', $result['instanceName']);
                $this->activeInstance = $result['instanceName'];
            }

            Notification::make()
                ->title('Instância criada')
                ->body($result['instanceName'])
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Falha ao criar instância')
                ->body(Str::limit($exception->getMessage(), 240))
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Atualizar')
                ->icon('heroicon-o-arrow-path')
                ->action(fn (EvolutionInstanceService $instances) => $this->refreshInstances($instances)),
            Action::make('create')
                ->label('Nova instância')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => IntegrationSettings::evolutionApiReady())
                ->schema([
                    TextInput::make('instance_name')
                        ->label('Nome da instância')
                        ->placeholder('ex: vendas, suporte')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Use apenas letras, números e hífen. Será normalizado automaticamente.'),
                ])
                ->action(function (array $data, EvolutionInstanceService $instances): void {
                    $this->createData['instance_name'] = $data['instance_name'] ?? '';
                    $this->createInstance($instances);
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evolution API')
                    ->description('Gerencie instâncias WhatsApp conectadas à Evolution API. URL e API Key ficam em Integrações API.')
                    ->schema([
                        View::make('filament.pages.manage-instances-status')
                            ->viewData(fn (): array => [
                                'apiReady' => IntegrationSettings::evolutionApiReady(),
                                'configured' => IntegrationSettings::evolutionConfigured(),
                                'baseUrl' => IntegrationSettings::evolutionBaseUrl(),
                                'activeInstance' => $this->activeInstance,
                            ]),
                    ]),
                Section::make('Instâncias')
                    ->schema([
                        View::make('filament.pages.manage-instances-list')
                            ->viewData(fn (): array => [
                                'instances' => $this->instances,
                                'activeInstance' => $this->activeInstance,
                                'apiReady' => IntegrationSettings::evolutionApiReady(),
                            ]),
                    ]),
                Section::make('QR Code — conectar WhatsApp')
                    ->description('Escaneie com o WhatsApp → Aparelhos conectados → Conectar aparelho.')
                    ->visible(fn (): bool => filled($this->qrBase64))
                    ->schema([
                        View::make('filament.pages.manage-instances-qr')
                            ->viewData(fn (): array => [
                                'qrInstance' => $this->qrInstance,
                                'qrBase64' => $this->qrBase64,
                                'qrCode' => $this->qrCode,
                                'qrPairingCode' => $this->qrPairingCode,
                            ]),
                    ]),
            ]);
    }

    /**
     * @param  list<EvolutionInstanceSummary>  $instances
     * @return list<array{name: string, state: string, instanceId: ?string, profileName: ?string, ownerJid: ?string, stateLabel: string, stateColor: string}>
     */
    private function serializeInstances(array $instances): array
    {
        return array_map(fn (EvolutionInstanceSummary $instance): array => [
            'name' => $instance->name,
            'state' => $instance->state,
            'instanceId' => $instance->instanceId,
            'profileName' => $instance->profileName,
            'ownerJid' => $instance->ownerJid,
            'stateLabel' => $instance->stateLabel(),
            'stateColor' => $instance->stateColor(),
        ], $instances);
    }
}
