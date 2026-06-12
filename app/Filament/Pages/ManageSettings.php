<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | UnitEnum | null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Configuração';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Configuração geral';
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name'),
            'site_tagline' => Setting::get('site_tagline'),
            'support_email' => Setting::get('support_email'),
            'checkout_completo_url' => Setting::get('checkout_completo_url'),
            'footer_text' => Setting::get('footer_text'),
            'logo_path' => filled(Setting::get('logo_path')) ? [Setting::get('logo_path')] : [],
            'primary_color' => Setting::get('primary_color', '#1a5c38'),
            'audio_subscription_title' => Setting::get('audio_subscription_title', 'Biblioteca Bíblica en Audio'),
            'audio_subscription_price' => Setting::get('audio_subscription_price', 'USD $4.90/mes'),
            'audio_subscription_checkout_url' => Setting::get('audio_subscription_checkout_url', '#'),
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
                Section::make('Geral')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nome do site (visível ao cliente)')
                            ->required(),
                        Textarea::make('site_tagline')
                            ->label('Slogan do produto (visível ao cliente)')
                            ->rows(2),
                        TextInput::make('support_email')
                            ->label('E-mail de suporte (visível ao cliente)')
                            ->email(),
                        Textarea::make('footer_text')
                            ->label('Texto do rodapé (visível ao cliente)')
                            ->rows(2),
                    ]),
                Section::make('Aparência')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('settings'),
                        ColorPicker::make('primary_color')
                            ->label('Cor principal'),
                    ]),
                Section::make('Link de checkout')
                    ->description('URL externa do Plan Completo (visível ao cliente quando o acesso está bloqueado).')
                    ->schema([
                        TextInput::make('checkout_completo_url')
                            ->label('Checkout Plan Completo')
                            ->url()
                            ->columnSpanFull(),
                    ]),
                Section::make('Assinatura de áudio')
                    ->description('Textos exibidos na área do cliente para a Biblioteca Bíblica en Audio Premium.')
                    ->schema([
                        TextInput::make('audio_subscription_title')
                            ->label('Título da assinatura (espanhol)')
                            ->required(),
                        TextInput::make('audio_subscription_price')
                            ->label('Preço sugerido — texto (espanhol)')
                            ->placeholder('USD $4.90/mes'),
                        TextInput::make('audio_subscription_checkout_url')
                            ->label('URL externa de checkout')
                            ->url()
                            ->placeholder('https://...'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = array_values($value)[0] ?? '';
            }

            Setting::set($key, $value ?? '');
        }

        Notification::make()
            ->title('Configuração salva')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar configuração')
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
