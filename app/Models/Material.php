<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    protected $fillable = [
        'category_id',
        'plan_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'type',
        'pdf_path',
        'pdf_page_count',
        'content',
        'status',
        'sort_order',
        'is_upsell',
        'external_checkout_url',
        'hotmart_product_code',
        'upsell_title',
        'upsell_subtitle',
        'upsell_gallery',
        'preview_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'status' => MaterialStatus::class,
            'sort_order' => 'integer',
            'pdf_page_count' => 'integer',
            'is_upsell' => 'boolean',
            'upsell_gallery' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserMaterialProgress::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(MaterialUnlock::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', MaterialStatus::Published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === MaterialStatus::Published;
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path && Storage::disk('private')->exists($this->pdf_path);
    }

    public function hasPreviewPdf(): bool
    {
        return $this->preview_pdf_path && Storage::disk('private')->exists($this->preview_pdf_path);
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($this->cover_image)) {
            return $disk->url($this->cover_image);
        }

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $path = $this->cover_image.'.'.$extension;

            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }

    public function upsellGalleryUrls(): array
    {
        return collect($this->upsell_gallery ?? [])
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->all();
    }
}
