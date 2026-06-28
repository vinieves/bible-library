<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ForumPersona extends Model
{
    protected $fillable = [
        'name',
        'photo',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($this->photo)) {
            return $disk->url($this->photo);
        }

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $path = $this->photo.'.'.$extension;

            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }
}
