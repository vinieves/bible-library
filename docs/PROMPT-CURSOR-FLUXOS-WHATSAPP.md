# PROMPT PARA CURSOR — Sistema de Fluxos WhatsApp (Bible Library / Laravel + Filament)

---

## CONTEXTO DO PROJETO

Projeto: **Bible Library** — Laravel 13 + Filament 4, dark theme personalizado.  
Evolution API em uso: `https://wpp.mediamrkt.online`, instância `biblioteca`.  
Já existe toda a infraestrutura de WhatsApp (`EvolutionApiService`, jobs, logs, fila `database`, Supervisor).  
O sistema de envio atual já funciona: suporta `sendText` via `POST /message/sendText/{instance}`.

Referência visual (prints enviados):
- **Imagem 3**: tela do painel admin atual — dark, sidebar esquerda, cards com status.
- **Imagem 4**: listagem de Fluxos com card "Novo Fluxo", badge RASCUNHO, data, contagem de blocos.
- **Imagem 1 (Leona)**: modal "Editar Mensagem" com blocos de conteúdo: Texto, Imagem, Vídeo, Áudio, Intervalo, Contato, Arquivo, Sticker — e delay de digitando.
- **Imagem 2 (Leona)**: canvas de fluxo com nós visuais drag-and-drop: nó "Início" (verde) conectado a nó "Mensagem" (azul) por seta.

---

## OBJETIVO

Criar um **sistema completo de Fluxos de Mensagens WhatsApp** dentro do painel Filament existente, mantendo a estilização atual do projeto.

Um **Fluxo** é uma sequência ordenada de passos de mensagens enviados ao WhatsApp de um contato. Cada passo pode ser: texto, imagem, vídeo, áudio, arquivo, ou intervalo (delay). O fluxo é executado em ordem, com delays configuráveis entre os passos.

---

## ARQUITETURA COMPLETA A IMPLEMENTAR

### 1. MIGRATIONS (banco de dados)

#### Tabela `whatsapp_flows`
```sql
id               bigint PK autoincrement
name             varchar(255)           -- nome do fluxo
description      text nullable
status           enum('draft','active','inactive') default 'draft'
trigger_type     enum('manual','webhook','scheduled') default 'manual'
trigger_event    varchar(100) nullable  -- ex: PURCHASE_APPROVED (se trigger=webhook)
is_active        boolean default false
steps_count      int default 0          -- cache da contagem de passos
created_at, updated_at timestamps
```

#### Tabela `whatsapp_flow_steps`
```sql
id               bigint PK autoincrement
flow_id          bigint FK -> whatsapp_flows.id (cascade delete)
order            int                    -- ordem do passo no fluxo (1, 2, 3...)
type             enum('text','image','video','audio','file','delay')
content          text nullable          -- texto da mensagem (type=text) ou URL/base64 (mídia)
caption          varchar(1000) nullable -- legenda (para imagens/vídeos/arquivos)
file_name        varchar(255) nullable  -- nome do arquivo (para documentos)
media_url        varchar(2000) nullable -- URL pública da mídia
delay_seconds    int default 0          -- delay em segundos antes de enviar ESTE passo
typing_delay     int default 3          -- segundos de "digitando" antes de enviar (apenas text)
created_at, updated_at timestamps
```

#### Tabela `whatsapp_flow_executions`
```sql
id               bigint PK autoincrement
flow_id          bigint FK -> whatsapp_flows.id
phone            varchar(30)
phone_normalized varchar(30)
user_id          bigint nullable FK -> users.id
trigger          varchar(50)            -- 'manual', 'webhook', 'scheduled'
status           enum('pending','running','completed','failed') default 'pending'
current_step     int default 0
total_steps      int default 0
started_at       timestamp nullable
completed_at     timestamp nullable
error_message    text nullable
created_at, updated_at timestamps
```

#### Tabela `whatsapp_flow_execution_logs`
```sql
id               bigint PK autoincrement
execution_id     bigint FK -> whatsapp_flow_executions.id (cascade delete)
step_id          bigint FK -> whatsapp_flow_steps.id
step_order       int
step_type        varchar(20)
status           enum('sent','failed','skipped')
http_status      int nullable
error_message    text nullable
evolution_response json nullable
sent_at          timestamp nullable
created_at, updated_at timestamps
```

---

### 2. MODELS

#### `app/Models/WhatsAppFlow.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppFlow extends Model
{
    protected $fillable = [
        'name', 'description', 'status', 'trigger_type',
        'trigger_event', 'is_active', 'steps_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'steps_count' => 'integer',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WhatsAppFlowStep::class, 'flow_id')->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WhatsAppFlowExecution::class, 'flow_id');
    }
}
```

#### `app/Models/WhatsAppFlowStep.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFlowStep extends Model
{
    protected $fillable = [
        'flow_id', 'order', 'type', 'content', 'caption',
        'file_name', 'media_url', 'delay_seconds', 'typing_delay',
    ];

    protected $casts = [
        'order' => 'integer',
        'delay_seconds' => 'integer',
        'typing_delay' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }
}
```

#### `app/Models/WhatsAppFlowExecution.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppFlowExecution extends Model
{
    protected $fillable = [
        'flow_id', 'phone', 'phone_normalized', 'user_id',
        'trigger', 'status', 'current_step', 'total_steps',
        'started_at', 'completed_at', 'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'current_step' => 'integer',
        'total_steps' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsAppFlowExecutionLog::class, 'execution_id');
    }
}
```

#### `app/Models/WhatsAppFlowExecutionLog.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppFlowExecutionLog extends Model
{
    protected $fillable = [
        'execution_id', 'step_id', 'step_order', 'step_type',
        'status', 'http_status', 'error_message', 'evolution_response', 'sent_at',
    ];

    protected $casts = [
        'evolution_response' => 'array',
        'sent_at' => 'datetime',
    ];
}
```

---

### 3. ENUMS

#### `app/Enums/WhatsAppFlowStatus.php`
```php
<?php
namespace App\Enums;

enum WhatsAppFlowStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }
}
```

#### `app/Enums/WhatsAppFlowStepType.php`
```php
<?php
namespace App\Enums;

enum WhatsAppFlowStepType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';
    case Delay = 'delay';

    public function label(): string
    {
        return match($this) {
            self::Text => 'Texto',
            self::Image => 'Imagem',
            self::Video => 'Vídeo',
            self::Audio => 'Áudio',
            self::File => 'Arquivo',
            self::Delay => 'Intervalo',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Text => 'heroicon-o-chat-bubble-left',
            self::Image => 'heroicon-o-photo',
            self::Video => 'heroicon-o-video-camera',
            self::Audio => 'heroicon-o-microphone',
            self::File => 'heroicon-o-document',
            self::Delay => 'heroicon-o-clock',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Text => '#3b82f6',
            self::Image => '#22c55e',
            self::Video => '#a855f7',
            self::Audio => '#f97316',
            self::File => '#6366f1',
            self::Delay => '#14b8a6',
        };
    }
}
```

#### `app/Enums/WhatsAppFlowTriggerType.php`
```php
<?php
namespace App\Enums;

enum WhatsAppFlowTriggerType: string
{
    case Manual = 'manual';
    case Webhook = 'webhook';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match($this) {
            self::Manual => 'Manual',
            self::Webhook => 'Webhook (Hotmart)',
            self::Scheduled => 'Agendado',
        };
    }
}
```

---

### 4. SERVICES

#### `app/Services/WhatsAppFlowService.php`

Responsabilidade: orquestrar a execução de um fluxo completo.

```php
<?php
namespace App\Services;

use App\Models\WhatsAppFlow;
use App\Models\WhatsAppFlowExecution;
use App\Jobs\ExecuteWhatsAppFlowJob;
use App\Services\Webhooks\PhoneNumber;

class WhatsAppFlowService
{
    /**
     * Inicia a execução de um fluxo para um telefone.
     * Cria o registro de execução e despacha o job.
     */
    public function dispatch(WhatsAppFlow $flow, string $phone, string $trigger = 'manual', ?int $userId = null): WhatsAppFlowExecution
    {
        $normalized = PhoneNumber::normalize($phone);

        $execution = WhatsAppFlowExecution::create([
            'flow_id' => $flow->id,
            'phone' => $phone,
            'phone_normalized' => $normalized,
            'user_id' => $userId,
            'trigger' => $trigger,
            'status' => 'pending',
            'current_step' => 0,
            'total_steps' => $flow->steps()->count(),
        ]);

        ExecuteWhatsAppFlowJob::dispatch($execution->id);

        return $execution;
    }
}
```

#### `app/Services/WhatsAppFlowStepSenderService.php`

Responsabilidade: enviar um único passo do fluxo via Evolution API.

```php
<?php
namespace App\Services;

use App\Models\WhatsAppFlowStep;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowStepSenderService
{
    private string $baseUrl;
    private string $instance;
    private string $apiKey;

    public function __construct()
    {
        $settings = app(IntegrationSettings::class);
        $this->baseUrl = rtrim($settings->evolutionBaseUrl(), '/');
        $this->instance = $settings->evolutionInstance();
        $this->apiKey = $settings->evolutionApiKey();
    }

    /**
     * Envia o passo e retorna ['success' => bool, 'http_status' => int, 'response' => array|null, 'error' => string|null]
     */
    public function send(WhatsAppFlowStep $step, string $phoneNormalized): array
    {
        try {
            // Delay de intervalo (type=delay): apenas dorme, não envia nada
            if ($step->type === 'delay') {
                sleep($step->delay_seconds);
                return ['success' => true, 'http_status' => 200, 'response' => null, 'error' => null];
            }

            // Delay antes do envio (qualquer tipo)
            if ($step->delay_seconds > 0) {
                sleep($step->delay_seconds);
            }

            return match($step->type) {
                'text'  => $this->sendText($step, $phoneNormalized),
                'image', 'video', 'file' => $this->sendMedia($step, $phoneNormalized),
                'audio' => $this->sendAudio($step, $phoneNormalized),
                default => ['success' => false, 'http_status' => 0, 'response' => null, 'error' => "Tipo desconhecido: {$step->type}"],
            };
        } catch (\Throwable $e) {
            Log::error('WhatsAppFlowStepSenderService error', ['step_id' => $step->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'http_status' => 0, 'response' => null, 'error' => $e->getMessage()];
        }
    }

    private function sendText(WhatsAppFlowStep $step, string $phone): array
    {
        // Simular "digitando" se typing_delay > 0 usando endpoint sendPresence (opcional)
        // ou simplesmente adicionar delay antes

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
            'number' => $phone,
            'text'   => $step->content,
            'delay'  => ($step->typing_delay ?? 3) * 1000, // delay em ms para simular digitando
        ]);

        return $this->parseResponse($response);
    }

    private function sendMedia(WhatsAppFlowStep $step, string $phone): array
    {
        $mediatype = match($step->type) {
            'image' => 'image',
            'video' => 'video',
            'file'  => 'document',
            default => 'document',
        };

        $payload = [
            'number'    => $phone,
            'mediatype' => $mediatype,
            'media'     => $step->media_url ?? $step->content,
            'caption'   => $step->caption ?? '',
            'fileName'  => $step->file_name ?? '',
            'delay'     => ($step->delay_seconds ?? 0) * 1000,
        ];

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendMedia/{$this->instance}", $payload);

        return $this->parseResponse($response);
    }

    private function sendAudio(WhatsAppFlowStep $step, string $phone): array
    {
        // Evolution API: POST /message/sendAudio/{instance}
        // Body: { "number": "...", "audio": "<base64 ou URL>", "encoding": true }
        $payload = [
            'number'   => $phone,
            'audio'    => $step->media_url ?? $step->content,
            'encoding' => true,
            'delay'    => ($step->delay_seconds ?? 0) * 1000,
        ];

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendAudio/{$this->instance}", $payload);

        return $this->parseResponse($response);
    }

    private function parseResponse($response): array
    {
        $status = $response->status();
        $body = $response->json();

        if ($response->successful()) {
            return ['success' => true, 'http_status' => $status, 'response' => $body, 'error' => null];
        }

        $error = $body['message'] ?? $response->body();
        return ['success' => false, 'http_status' => $status, 'response' => $body, 'error' => $error];
    }
}
```

---

### 5. JOB

#### `app/Jobs/ExecuteWhatsAppFlowJob.php`

```php
<?php
namespace App\Jobs;

use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use App\Services\WhatsAppFlowStepSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteWhatsAppFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Fluxos não devem ter retry automático — cada passo tem seu próprio controle
    public int $timeout = 600; // 10 minutos para fluxos longos

    public function __construct(private readonly int $executionId) {}

    public function handle(WhatsAppFlowStepSenderService $sender): void
    {
        $execution = WhatsAppFlowExecution::with(['flow.steps'])->findOrFail($this->executionId);

        $execution->update(['status' => 'running', 'started_at' => now()]);

        $steps = $execution->flow->steps;

        foreach ($steps as $step) {
            $execution->increment('current_step');

            $result = $sender->send($step, $execution->phone_normalized);

            WhatsAppFlowExecutionLog::create([
                'execution_id'      => $execution->id,
                'step_id'           => $step->id,
                'step_order'        => $step->order,
                'step_type'         => $step->type,
                'status'            => $result['success'] ? 'sent' : 'failed',
                'http_status'       => $result['http_status'],
                'error_message'     => $result['error'],
                'evolution_response'=> $result['response'],
                'sent_at'           => now(),
            ]);

            if (!$result['success']) {
                Log::warning("WhatsApp Flow passo falhou", [
                    'execution_id' => $execution->id,
                    'step_id' => $step->id,
                    'error' => $result['error'],
                ]);
                // Continua para o próximo passo mesmo com falha
            }
        }

        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        WhatsAppFlowExecution::where('id', $this->executionId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);

        Log::error("ExecuteWhatsAppFlowJob falhou", [
            'execution_id' => $this->executionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

### 6. FILAMENT — RESOURCE PRINCIPAL (Listagem de Fluxos)

#### `app/Filament/Resources/WhatsAppFlowResource.php`

Este Resource é o ponto de entrada do sistema. Mantenha **exatamente** a estilização atual do projeto (dark theme, sidebar igual às outras páginas).

```php
<?php
namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppFlowResource\Pages;
use App\Models\WhatsAppFlow;
use App\Enums\WhatsAppFlowStatus;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppFlowResource extends Resource
{
    protected static ?string $model = WhatsAppFlow::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Fluxos';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Fluxo';
    protected static ?string $pluralModelLabel = 'Fluxos';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => WhatsAppFlowStatus::from($state)->label())
                    ->color(fn ($state) => WhatsAppFlowStatus::from($state)->color()),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Blocos')
                    ->suffix(' blocos')
                    ->icon('heroicon-o-bolt'),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Gatilho')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_flow')
                    ->label('Editar Fluxo')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
                Tables\Actions\Action::make('dispatch_test')
                    ->label('Enviar Teste')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label('Número de telefone')
                            ->placeholder('5511999999999')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        app(\App\Services\WhatsAppFlowService::class)
                            ->dispatch($record, $data['phone'], 'manual', auth()->id());
                        \Filament\Notifications\Notification::make()
                            ->title('Fluxo enfileirado!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('+ Criar Fluxo'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWhatsAppFlows::route('/'),
            'create' => Pages\CreateWhatsAppFlow::route('/create'),
            'edit'   => Pages\EditWhatsAppFlow::route('/{record}/edit'),
        ];
    }
}
```

---

### 7. FILAMENT — PÁGINA DE EDIÇÃO DO FLUXO (Editor de Passos)

#### `app/Filament/Resources/WhatsAppFlowResource/Pages/EditWhatsAppFlow.php`

Esta é a **página mais importante** do sistema — o editor visual de passos do fluxo, equivalente ao canvas da Leona (Imagem 2).

**Implementar como uma `EditRecord` page do Filament com um `Repeater` customizado** que renderiza os passos em lista ordenável (drag-and-drop via Filament's `sort` no Repeater).

```php
<?php
namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Filament\Resources\WhatsAppFlowResource;
use App\Enums\WhatsAppFlowStepType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppFlow extends EditRecord
{
    protected static string $resource = WhatsAppFlowResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([

            // — CONFIGURAÇÕES DO FLUXO —
            Forms\Components\Section::make('Configurações do Fluxo')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome do Fluxo')
                        ->required()
                        ->columnSpan(2),
                    Forms\Components\Textarea::make('description')
                        ->label('Descrição')
                        ->rows(2)
                        ->columnSpan(2),
                    Forms\Components\Select::make('trigger_type')
                        ->label('Gatilho')
                        ->options([
                            'manual'    => 'Manual',
                            'webhook'   => 'Webhook (Hotmart)',
                            'scheduled' => 'Agendado',
                        ])
                        ->default('manual')
                        ->reactive(),
                    Forms\Components\Select::make('trigger_event')
                        ->label('Evento (Hotmart)')
                        ->options([
                            'PURCHASE_APPROVED'           => 'Venda aprovada',
                            'PURCHASE_PROTEST'            => 'Pedido de reembolso',
                            'PURCHASE_CANCELED'           => 'Venda cancelada',
                            'PURCHASE_CHARGEBACK'         => 'Chargeback',
                            'PURCHASE_COMPLETE'           => 'Compra completa',
                            'PURCHASE_OUT_OF_SHOPPING_CART' => 'Abandonou carrinho',
                        ])
                        ->visible(fn ($get) => $get('trigger_type') === 'webhook'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Fluxo ativo')
                        ->helperText('Somente fluxos ativos são disparados automaticamente'),
                ])
                ->columns(2)
                ->collapsible(),

            // — PASSOS DO FLUXO —
            Forms\Components\Section::make('Passos do Fluxo')
                ->description('Adicione e ordene os passos que serão enviados ao contato.')
                ->schema([
                    Forms\Components\Repeater::make('steps')
                        ->label('')
                        ->relationship('steps')
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->addActionLabel('+ Adicionar Passo')
                        ->schema([

                            Forms\Components\Select::make('type')
                                ->label('Tipo de passo')
                                ->options(collect(WhatsAppFlowStepType::cases())->mapWithKeys(
                                    fn ($case) => [$case->value => $case->label()]
                                ))
                                ->default('text')
                                ->required()
                                ->reactive()
                                ->columnSpanFull(),

                            // — CONTEÚDO TEXTO —
                            Forms\Components\RichEditor::make('content')
                                ->label('Conteúdo da Mensagem')
                                ->toolbarButtons(['bold', 'italic', 'strike', 'codeBlock'])
                                ->visible(fn ($get) => $get('type') === 'text')
                                ->required(fn ($get) => $get('type') === 'text')
                                ->columnSpanFull()
                                ->helperText('Placeholders disponíveis: {nome}, {email}, {telefone}, {producto}, {link_acceso}'),

                            // — DELAY/INTERVALO —
                            Forms\Components\Placeholder::make('delay_info')
                                ->label('Intervalo de espera')
                                ->content('Este passo apenas aguarda o tempo configurado antes de prosseguir.')
                                ->visible(fn ($get) => $get('type') === 'delay')
                                ->columnSpanFull(),

                            // — MÍDIA (imagem/vídeo/arquivo/áudio) —
                            Forms\Components\TextInput::make('media_url')
                                ->label('URL da Mídia')
                                ->url()
                                ->placeholder('https://exemplo.com/arquivo.jpg')
                                ->visible(fn ($get) => in_array($get('type'), ['image', 'video', 'audio', 'file']))
                                ->required(fn ($get) => in_array($get('type'), ['image', 'video', 'audio', 'file']))
                                ->columnSpanFull()
                                ->helperText('URL pública acessível da mídia'),

                            Forms\Components\TextInput::make('caption')
                                ->label('Legenda / Descrição')
                                ->visible(fn ($get) => in_array($get('type'), ['image', 'video', 'file']))
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('file_name')
                                ->label('Nome do arquivo')
                                ->visible(fn ($get) => $get('type') === 'file')
                                ->placeholder('documento.pdf')
                                ->columnSpan(1),

                            // — CONFIGURAÇÕES DE TIMING —
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('delay_seconds')
                                    ->label('Intervalo antes deste passo (seg)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(3600)
                                    ->suffix('segundos')
                                    ->helperText('Aguarda X segundos antes de executar este passo'),

                                Forms\Components\TextInput::make('typing_delay')
                                    ->label('Delay "digitando" (seg)')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->suffix('segundos')
                                    ->visible(fn ($get) => $get('type') === 'text')
                                    ->helperText('Tempo que aparecerá "digitando" no WhatsApp'),
                            ]),

                        ])
                        ->itemLabel(function (array $state): ?string {
                            $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
                            $label = $type?->label() ?? 'Passo';
                            $preview = '';
                            if ($state['type'] === 'text' && !empty($state['content'])) {
                                $preview = ' — ' . \Illuminate\Support\Str::limit(strip_tags($state['content']), 40);
                            } elseif (!empty($state['media_url'])) {
                                $preview = ' — ' . \Illuminate\Support\Str::limit($state['media_url'], 40);
                            } elseif ($state['type'] === 'delay') {
                                $preview = ' — ' . ($state['delay_seconds'] ?? 0) . 's';
                            }
                            return $label . $preview;
                        }),
                ]),

        ]);
    }

    protected function afterSave(): void
    {
        // Atualiza o cache de steps_count
        $this->record->update([
            'steps_count' => $this->record->steps()->count(),
        ]);
    }
}
```

---

### 8. LISTAGEM (Pages/ListWhatsAppFlows.php)

#### `app/Filament/Resources/WhatsAppFlowResource/Pages/ListWhatsAppFlows.php`

```php
<?php
namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Filament\Resources\WhatsAppFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppFlows extends ListRecords
{
    protected static string $resource = WhatsAppFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Criar Fluxo'),
        ];
    }
}
```

---

### 9. RESOURCE AUXILIAR — Log de Execuções

#### `app/Filament/Resources/WhatsAppFlowExecutionResource.php`

Tela de histórico de execuções dos fluxos (equivalente ao "Disparos WhatsApp" existente).

```php
<?php
namespace App\Filament\Resources;

use App\Models\WhatsAppFlowExecution;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppFlowExecutionResource extends Resource
{
    protected static ?string $model = WhatsAppFlowExecution::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Execuções de Fluxo';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Execução';
    protected static ?string $pluralModelLabel = 'Execuções de Fluxo';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flow.name')
                    ->label('Fluxo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_normalized')
                    ->label('Telefone'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'running',
                        'success' => 'completed',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('current_step')
                    ->label('Passos')
                    ->formatStateUsing(fn ($record) => "{$record->current_step}/{$record->total_steps}"),
                Tables\Columns\TextColumn::make('trigger')
                    ->label('Gatilho')
                    ->badge(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Concluído')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\WhatsAppFlowExecutionResource\Pages\ListWhatsAppFlowExecutions::route('/'),
        ];
    }
}
```

---

### 10. ROTA DE NAVEGAÇÃO

No `AdminPanelProvider` (ou onde estiver o registro de resources), adicionar:

```php
// Dentro do método panel() em app/Providers/Filament/AdminPanelProvider.php
->resources([
    // ... resources existentes ...
    \App\Filament\Resources\WhatsAppFlowResource::class,
    \App\Filament\Resources\WhatsAppFlowExecutionResource::class,
])
```

---

## ENDPOINTS DA EVOLUTION API UTILIZADOS

Com base na documentação oficial e nos arquivos existentes do projeto:

```
POST {base_url}/message/sendText/{instance}
Body: { "number": "55...", "text": "...", "delay": 3000 }

POST {base_url}/message/sendMedia/{instance}
Body: { "number": "55...", "mediatype": "image|video|document", "media": "<url>", "caption": "...", "fileName": "...", "delay": 0 }

POST {base_url}/message/sendAudio/{instance}
Body: { "number": "55...", "audio": "<url_ou_base64>", "encoding": true, "delay": 0 }
```

Headers sempre: `apikey: {evolution_api_key}`, `Content-Type: application/json`

---

## REGRAS CRÍTICAS DE IMPLEMENTAÇÃO

1. **NÃO criar novos serviços de configuração.** Usar o `IntegrationSettings` existente em `app/Support/IntegrationSettings.php` para URL, instância e API Key.

2. **NÃO criar normalização de telefone.** Usar o `PhoneNumber::normalize()` existente em `app/Services/Webhooks/PhoneNumber.php`.

3. **Fila**: usar `QUEUE_CONNECTION=database` (já configurado). O job `ExecuteWhatsAppFlowJob` deve ir para a mesma fila dos jobs existentes.

4. **Estilização**: manter exatamente o mesmo dark theme, cores, fontes e estrutura de navegação do painel atual. O grupo de navegação deve ser **"Sistema"** (igual ao "Mensagens", "Disparos WhatsApp" etc. já existentes).

5. **Repeater com relacionamento**: o Filament Repeater com `->relationship('steps')` e `->orderColumn('order')` já gerencia automaticamente INSERT/UPDATE/DELETE dos passos ao salvar. Não implementar CRUD manual de passos.

6. **steps_count**: sempre atualizar após salvar via `afterSave()`.

7. **Não usar `ShouldBeUnique` no Job de fluxo** — o mesmo fluxo pode ser executado múltiplas vezes para diferentes contatos simultaneamente.

8. **Delay real vs delay de digitando**: 
   - `delay_seconds`: o Job dorme antes de enviar o passo (usando `sleep()`)
   - `typing_delay` (apenas texto): passado como `delay` em ms no body da requisição para a Evolution API simular "digitando"

9. **Failure handling no job**: se um passo falha, registrar no log e **continuar para o próximo passo** (não abortar o fluxo inteiro). Apenas marcar a execução como `failed` se o job em si lançar exceção não capturada.

10. **Sem interface drag-and-drop canvas** (como na Leona): usar o **Repeater nativo do Filament com reordenação** (drag handle). Mais simples, robusto e compatível com o stack atual.

---

## ARQUIVOS A CRIAR (resumo)

```
database/migrations/
  ????_create_whatsapp_flows_table.php
  ????_create_whatsapp_flow_steps_table.php
  ????_create_whatsapp_flow_executions_table.php
  ????_create_whatsapp_flow_execution_logs_table.php

app/Models/
  WhatsAppFlow.php
  WhatsAppFlowStep.php
  WhatsAppFlowExecution.php
  WhatsAppFlowExecutionLog.php

app/Enums/
  WhatsAppFlowStatus.php
  WhatsAppFlowStepType.php
  WhatsAppFlowTriggerType.php

app/Services/
  WhatsAppFlowService.php
  WhatsAppFlowStepSenderService.php

app/Jobs/
  ExecuteWhatsAppFlowJob.php

app/Filament/Resources/
  WhatsAppFlowResource.php
  WhatsAppFlowResource/Pages/ListWhatsAppFlows.php
  WhatsAppFlowResource/Pages/CreateWhatsAppFlow.php
  WhatsAppFlowResource/Pages/EditWhatsAppFlow.php
  WhatsAppFlowExecutionResource.php
  WhatsAppFlowExecutionResource/Pages/ListWhatsAppFlowExecutions.php
```

---

## ARQUIVOS A MODIFICAR

```
app/Providers/Filament/AdminPanelProvider.php
  → Registrar os 2 novos Resources
```

---

## TESTES MANUAIS APÓS IMPLEMENTAÇÃO

1. Criar um fluxo com 3 passos: Texto → Delay (5s) → Texto
2. Clicar em "Enviar Teste" na listagem, informar um número válido
3. Verificar em `whatsapp_flow_executions` que o registro foi criado com `status = pending`
4. Rodar `php artisan queue:work` e confirmar que muda para `completed`
5. Verificar `whatsapp_flow_execution_logs` com os 3 passos registrados

---

## CONTEXTO TÉCNICO ADICIONAL

- Laravel 13, PHP 8.3+
- Filament 4.x (sintaxe v4: `BadgeColumn`, `TextColumn`, `Section`, `Repeater`, `Toggle`, etc.)
- Dark theme já aplicado globalmente — não adicionar classes CSS manuais de cor
- Migrations: usar `php artisan make:migration` e depois completar o schema
- Jobs na fila `database`: `php artisan queue:work database`
- Supervisor já configurado para processar a fila automaticamente em produção
