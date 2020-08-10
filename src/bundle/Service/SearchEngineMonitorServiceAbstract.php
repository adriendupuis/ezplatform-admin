<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

abstract class SearchEngineMonitorServiceAbstract
{
    abstract public function ping(): bool;

    abstract public function getOsMetrics(): array;

    /**
     * Formats bytes using binary base (1 KB = 2^10 B = 1024 Bytes) base.
     * Returns a string ending with the largest unit possible and a starting with the corresponding float with $precision digits after the decimal separator.
     */
    public static function formatBytes(float $bytes, int $precision = 2, string $unit = null): string
    {
        $base = 1024;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($unit) {
            $pow = array_search(strtoupper($unit), $units);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units: ".implode(', ', $units));
            }
        } else {
            $pow = min(floor(log($bytes, $base)), count($units) - 1);
        }

        return round($bytes / pow($base, $pow), $precision).' '.$units[$pow];
    }
}