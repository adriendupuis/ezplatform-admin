<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

abstract class SearchEngineMonitorServiceAbstract
{
    abstract public function ping(): bool;

    abstract public function getOsMetrics(): array;

    /**
     * Formats bytes using binary (default) base (1 KiB = 2^10 B = 1024 Bytes) base or metric base (1 kB = 10^3 B = 1000 B)
     * Returns a string ending with the largest unit possible and a starting with the corresponding float with $precision digits after the decimal separator.
     */
    public static function formatBytes(float $bytes, int $precision = 2, string $unit = null, int $base = 1024): string
    {
        $units = [
            1000 => ['B', 'kB', 'MB', 'GB', 'TB'],
            1024 => ['B', 'KiB', 'MiB', 'GiB', 'TiB'],
        ];
        if (array_key_exists($base, $units)) {
            $baseUnits = $units[$base];
        } else {
            throw new \InvalidArgumentException('Base must be '.implode(' or ', array_keys($units)));
        }
        if ($unit) {
            $pow = array_search($unit, $baseUnits);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units for base $base: ".implode(', ', $baseUnits));
            }
        } else {
            $pow = min(floor(log($bytes, $base)), count($baseUnits) - 1);
        }

        return number_format($bytes / pow($base, $pow), $precision, '.', '').' '.$baseUnits[$pow];
    }
}
