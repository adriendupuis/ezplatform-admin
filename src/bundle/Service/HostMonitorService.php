<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

class HostMonitorService extends ServerMonitorServiceAbstract
{
    public function ping(): bool
    {
        return true;
    }

    public function getOsMetrics(): array
    {
        $rawMetrics = explode(PHP_EOL, str_replace(':', '', shell_exec('free --bytes;')));
        $keys = preg_split('/ +/', array_shift($rawMetrics));
        $metrics = [];
        foreach ($rawMetrics as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $values = preg_split('/ +/', $line);
            $metrics[$values[0]] = array_combine(array_slice($keys, 0, count($values)), $values);
        }

        return [shell_exec('hostname') => [
            'free_physical_memory' => (int) $metrics['Mem']['free'],
            'total_physical_memory' => (int) $metrics['Mem']['total'],
            'used_physical_memory' => (int) $metrics['Mem']['used'],
            'free_physical_memory_human_readable' => self::formatBytes($metrics['Mem']['free']),
            'total_physical_memory_human_readable' => self::formatBytes($metrics['Mem']['total']),
            'used_physical_memory_human_readable' => self::formatBytes($metrics['Mem']['used']),
            'free_swap_space' => (int) $metrics['Swap']['free'],
            'total_swap_space' => (int) $metrics['Swap']['total'],
            'used_swap_space' => (int) $metrics['Swap']['used'],
            'free_swap_space_human_readable' => self::formatBytes($metrics['Swap']['free']),
            'total_swap_space_human_readable' => self::formatBytes($metrics['Swap']['total']),
            'used_swap_space_human_readable' => self::formatBytes($metrics['Swap']['used']),
        ]];
    }
}
