<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppPendingInboundResponse extends Model
{
    protected $fillable = [
        'phone_normalized',
        'instance_name',
        'message_id',
        'remote_jid',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }
}
