<?php

namespace App\DataTransferObjects;

readonly class EvolutionInstanceSummary
{
    public function __construct(
        public string $name,
        public string $state,
        public ?string $instanceId = null,
        public ?string $profileName = null,
        public ?string $ownerJid = null,
    ) {}

    public function stateLabel(): string
    {
        return match (strtolower($this->state)) {
            'open' => 'Conectado',
            'connecting' => 'Conectando',
            'close', 'closed' => 'Desconectado',
            default => ucfirst($this->state),
        };
    }

    public function stateColor(): string
    {
        return match (strtolower($this->state)) {
            'open' => 'success',
            'connecting' => 'warning',
            'close', 'closed' => 'danger',
            default => 'gray',
        };
    }
}
