<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Registrar apenas logins de usuários do app (ignora administradores do /admin).
        if (! $user instanceof User || $user->is_admin) {
            return;
        }

        $request = request();

        LoginLog::create([
            'user_id' => $user->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
