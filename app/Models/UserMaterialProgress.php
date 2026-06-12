<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMaterialProgress extends Model
{
    protected $table = 'user_material_progress';

    protected $fillable = [
        'user_id',
        'material_id',
        'is_studied',
        'is_favorite',
        'last_page_read',
        'current_page',
        'studied_at',
    ];

    protected function casts(): array
    {
        return [
            'is_studied' => 'boolean',
            'is_favorite' => 'boolean',
            'last_page_read' => 'integer',
            'current_page' => 'integer',
            'studied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function completionPercent(?Material $material = null): int
    {
        if ($this->is_studied) {
            return 100;
        }

        $material ??= $this->material;
        $totalPages = (int) ($material?->pdf_page_count ?? 0);

        if ($totalPages > 0 && $this->last_page_read > 0) {
            return min(100, (int) round(($this->last_page_read / $totalPages) * 100));
        }

        return 0;
    }

    public function statusLabel(?Material $material = null): string
    {
        if ($this->is_studied) {
            return 'Estudiado';
        }

        $material ??= $this->material;
        $totalPages = (int) ($material?->pdf_page_count ?? 0);

        if ($totalPages > 0 && $this->last_page_read > 0) {
            return "Página {$this->last_page_read} de {$totalPages}";
        }

        return 'Sin iniciar';
    }
}
