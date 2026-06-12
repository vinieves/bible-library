<?php

use App\Http\Controllers\Api\Webhook\PurchaseWebhookController;
use App\Http\Middleware\VerifyWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:webhooks'])
    ->prefix('webhooks')
    ->group(function () {
        Route::post('{platform}', PurchaseWebhookController::class)
            ->middleware(VerifyWebhookSecret::class)
            ->where('platform', 'hotmart|generic');
    });
