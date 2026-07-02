<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

final class VaultRegistry
{
    private const REGISTRY_KEY = 'vgta_vault_registry_index';

    public static function addToIndex(string $optionName): void
    {
        $index = \get_option(self::REGISTRY_KEY, []);
        if (!\is_array($index)) {
            $index = [];
        }

        if (!isset($index[$optionName])) {
            $index[$optionName] = true;
            \update_option(self::REGISTRY_KEY, $index, false);
        }
    }

    public static function removeFromIndex(string $optionName): void
    {
        $index = \get_option(self::REGISTRY_KEY, []);
        if (\is_array($index) && isset($index[$optionName])) {
            unset($index[$optionName]);
            \update_option(self::REGISTRY_KEY, $index, false);
        }
    }

    /**
     * @return list<string>
     */
    public static function getIndex(): array
    {
        $index = \get_option(self::REGISTRY_KEY, []);
        if (empty($index) || !\is_array($index)) {
            return [];
        }

        if (isset($index[0])) {
            $migratedIndex = [];
            foreach ($index as $value) {
                if (\is_string($value) && $value !== '') {
                    $migratedIndex[$value] = true;
                }
            }

            \update_option(self::REGISTRY_KEY, $migratedIndex, false);
            return \array_keys($migratedIndex);
        }

        return \array_keys($index);
    }
}
