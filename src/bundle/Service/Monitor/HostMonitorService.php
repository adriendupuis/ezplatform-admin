<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service\Monitor;

class HostMonitorService extends ServerMonitorServiceAbstract
{
    public function ping(): bool
    {
        return true;
    }

    public function getMetrics(): array
    {
        //TODO
        //- test that `free` is available
        //- use something more cros-platform than `free`
        $rawMetrics = explode(PHP_EOL, str_replace(':', '', shell_exec('free --bytes;')));
        $keys = preg_split('/ +/', array_shift($rawMetrics));
        $metrics = [];
        foreach ($rawMetrics as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $values = preg_split('/ +/', $line);
            $metrics[$values[0]] = array_combine(array_slice($keys, 1, count($values) - 1), array_slice($values, 1));
        }
        $metrics['Mem']['used_sum'] = 0;
        foreach (['used', 'shared', 'buff/cache', 'buffers', 'cache'] as $usedKey) {
            if (array_key_exists($usedKey, $metrics['Mem'])) {
                $metrics['Mem']['used_sum'] += $metrics['Mem'][$usedKey];
            }
        }

        return [shell_exec('hostname') => [
            'free_physical_memory' => (int) $metrics['Mem']['free'],
            'total_physical_memory' => (int) $metrics['Mem']['total'],
            'used_physical_memory' => (int) $metrics['Mem']['used_sum'],
            'free_physical_memory_human' => self::formatBytes($metrics['Mem']['free']),
            'total_physical_memory_human' => self::formatBytes($metrics['Mem']['total']),
            'used_physical_memory_human' => self::formatBytes($metrics['Mem']['used_sum']),
            'free_physical_memory_percent' => self::formatPercent($metrics['Mem']['free'] / $metrics['Mem']['total']),
            'used_physical_memory_percent' => self::formatPercent($metrics['Mem']['used_sum'] / $metrics['Mem']['total']),
            'free_swap_space' => (int) $metrics['Swap']['free'],
            'total_swap_space' => (int) $metrics['Swap']['total'],
            'used_swap_space' => (int) $metrics['Swap']['used'],
            'free_swap_space_human' => self::formatBytes($metrics['Swap']['free']),
            'total_swap_space_human' => self::formatBytes($metrics['Swap']['total']),
            'used_swap_space_human' => self::formatBytes($metrics['Swap']['used']),
            'free_swap_space_percent' => self::formatPercent($metrics['Swap']['free'] / $metrics['Swap']['total']),
            'used_swap_space_percent' => self::formatPercent($metrics['Swap']['used'] / $metrics['Swap']['total']),
        ]];
    }
}
