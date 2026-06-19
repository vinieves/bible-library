<?php

namespace App\Support;

use App\Services\EvolutionInstanceService;
use Throwable;

class EvolutionInstanceOptions
{
    /**
     * @return array<string, string>
     */
    public static function selectOptions(?string ...$includeNames): array
    {
        $options = static::fetchRemoteOptions();

        foreach ($includeNames as $name) {
            if (filled($name) && ! isset($options[$name])) {
                $options[$name] = (string) $name;
            }
        }

        foreach (static::fallbackFromSettings() as $name => $label) {
            if (! isset($options[$name])) {
                $options[$name] = $label;
            }
        }

        ksort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function fetchRemoteOptions(): array
    {
        if (! IntegrationSettings::evolutionApiReady()) {
            return [];
        }

        try {
            $instances = app(EvolutionInstanceService::class)->fetchAll();

            if ($instances === []) {
                return [];
            }

            $options = [];

            foreach ($instances as $instance) {
                $options[$instance->name] = $instance->name.' · '.$instance->stateLabel();
            }

            return $options;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public static function fallbackFromSettings(): array
    {
        $names = array_values(array_unique(array_filter(
            IntegrationSettings::trustedEvolutionInstances()
        )));

        if ($names === []) {
            return [];
        }

        return array_combine($names, $names);
    }
}
