<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Biblioteca Bíblica Digital', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'Toda la Biblia, explicada versículo por versículo', 'type' => 'text', 'group' => 'general'],
            ['key' => 'support_email', 'value' => 'soporte@biblioteca.test', 'type' => 'email', 'group' => 'general'],
            ['key' => 'checkout_completo_url', 'value' => 'https://checkout.ejemplo.com/plan-completo', 'type' => 'url', 'group' => 'checkout'],
            ['key' => 'footer_text', 'value' => '© Biblioteca Bíblica Digital. Todos los derechos reservados.', 'type' => 'text', 'group' => 'general'],
            ['key' => 'logo_path', 'value' => '', 'type' => 'image', 'group' => 'general'],
            ['key' => 'primary_color', 'value' => '#1a5c38', 'type' => 'color', 'group' => 'theme'],
            ['key' => 'audio_subscription_title', 'value' => 'Biblioteca Bíblica en Audio', 'type' => 'text', 'group' => 'audio'],
            ['key' => 'audio_subscription_price', 'value' => 'USD $4.90/mes', 'type' => 'text', 'group' => 'audio'],
            ['key' => 'audio_subscription_checkout_url', 'value' => '#', 'type' => 'url', 'group' => 'audio'],
            ['key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'integrations'],
            ['key' => 'evolution_base_url', 'value' => '', 'type' => 'url', 'group' => 'integrations'],
            ['key' => 'evolution_instance', 'value' => '', 'type' => 'text', 'group' => 'integrations'],
            ['key' => 'evolution_instance_messages', 'value' => '', 'type' => 'text', 'group' => 'integrations'],
            ['key' => 'evolution_instance_flows', 'value' => '', 'type' => 'text', 'group' => 'integrations'],
            ['key' => 'evolution_api_key', 'value' => '', 'type' => 'encrypted', 'group' => 'integrations'],
            ['key' => 'webhook_secret', 'value' => '', 'type' => 'encrypted', 'group' => 'integrations'],
            ['key' => 'hotmart_hottok', 'value' => '', 'type' => 'encrypted', 'group' => 'integrations'],
            ['key' => 'whatsapp_welcome_template', 'value' => "¡Hola {nome}! Su acceso a la Biblioteca Bíblica Digital ya está listo.\n\nEntre con su correo {email} en:\n{link_acceso}", 'type' => 'text', 'group' => 'integrations'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
