<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;

readonly class MemberActivityItem
{
    public function __construct(
        public string $type,
        public string $title,
        public string $subtitle,
        public int $percent,
        public bool $completed,
        public CarbonInterface $activityAt,
        public string $url,
    ) {}

    public function typeLabel(): string
    {
        return match ($this->type) {
            'bible' => 'Libro',
            'material' => 'Material',
            'video' => 'Video',
            'audio' => 'Audio',
            default => 'Actividad',
        };
    }

    public function statusLabel(): string
    {
        if ($this->completed || $this->percent >= 100) {
            return 'Completo';
        }

        if ($this->percent > 0) {
            return $this->percent.'%';
        }

        return 'Iniciado';
    }

    public function icon(): string
    {
        return match ($this->type) {
            'bible', 'material' => '📖',
            'video' => '🎬',
            'audio' => '🎧',
            default => '📌',
        };
    }
}
