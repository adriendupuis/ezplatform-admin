<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

abstract class ServerMonitorServiceAbstract
{
    abstract public function ping(): bool;

    abstract public function getOsMetrics(): array;

    /**
     * Formats bytes using binary (default) base (1 KiB = 2^10 B = 1024 Bytes) base or metric base (1 kB = 10^3 B = 1000 B)
     * Returns a string ending with the largest unit possible and a starting with the corresponding float with $precision digits after the decimal separator.
     */
    public static function formatBytes(int $bytes, int $precision = 2, string $unit = null, int $base = 1024): string
    {
        $unitSystems = [
            1000 => ['B', 'kB', 'MB', 'GB', 'TB'],
            1024 => ['B', 'KiB', 'MiB', 'GiB', 'TiB'],
        ];
        if (array_key_exists($base, $unitSystems)) {
            $units = $unitSystems[$base];
        } else {
            throw new \InvalidArgumentException('Base must be '.implode(' or ', array_keys($units)));
        }
        if ($unit) {
            $pow = array_search($unit, $units);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units for base $base: ".implode(', ', $baseUnits));
            }
        } else {
            $pow = self::getUnitPower($bytes, $base, $units);
            $formattedNumber = self::formatUnitNumber($bytes, $base, $pow, $precision);
            $pow = self::getUnitPower(round(pow($base, $pow) * (float) $formattedNumber), $base, $units); // Avoid case like 1024.00 MiB
        }

        if (0 === $pow) {
            $precision = 0;
        }
        $formattedNumber = self::formatUnitNumber($bytes, $base, $pow, $precision);

        return "$formattedNumber {$units[$pow]}";
    }

    private static function getUnitPower($bytes, $base, $units): int
    {
        return max(0, min(floor(log($bytes, $base)), count($units) - 1));
    }

    private static function formatUnitNumber($bytes, $base, $pow, $precision): string
    {
        return number_format($bytes / pow($base, $pow), $precision, '.', '');
    }
}
