<?php

namespace App\Contracts;

use App\DataTransferObjects\ParsedWebhookResult;
use Illuminate\Http\Request;

interface WebhookAdapterInterface
{
    public function parse(Request $request): ParsedWebhookResult;
}
