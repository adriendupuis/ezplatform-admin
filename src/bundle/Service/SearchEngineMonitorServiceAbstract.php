<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

abstract class SearchEngineMonitorServiceAbstract
{
    abstract public function ping(): bool;

    abstract public function getOsMetrics(): array;

    public static function formatBytes(float $bytes, int $precision = 2, string $unit = null): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($unit) {
            $pow = array_search(strtoupper($unit), $units);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units: ".implode(', ', $units));
            }
        } else {
            $pow = min(floor(log($bytes) / log(1024)), count($units) - 1);
        }

        return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow];
    }
}