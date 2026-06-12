<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageTemplateService
{
    public function renderWelcomeMessage(User $user, ?Purchase $purchase = null): string
    {
        $replacements = [
            '{nome}' => $user->name,
            '{email}' => $user->email,
            '{telefone}' => $purchase?->phone ?? '',
            '{producto}' => $purchase?->product?->title ?? '',
            '{link_acceso}' => route('login'),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            IntegrationSettings::whatsappTemplate()
        );
    }
}
