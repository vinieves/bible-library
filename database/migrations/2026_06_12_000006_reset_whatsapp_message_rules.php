<?php

use App\Models\WhatsAppMessageTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WhatsAppMessageTemplate::query()->delete();
    }

    public function down(): void
    {
        //
    }
};
