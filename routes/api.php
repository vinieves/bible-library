<?php

use App\Http\Controllers\Api\Webhook\EvolutionWebhookController;
use App\Http\Controllers\Api\Webhook\PurchaseWebhookController;
use App\Http\Middleware\VerifyEvolutionWebhook;
use App\Http\Middleware\VerifyWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:webhooks'])
    ->prefix('webhooks')
    ->group(function () {
        Route::post('evolution', EvolutionWebhookController::class)
            ->middleware(VerifyEvolutionWebhook::class);

        Route::post('{platform}', PurchaseWebhookController::class)
            ->middleware(VerifyWebhookSecret::class)
            ->where('platform', 'hotmart|generic');
    });
