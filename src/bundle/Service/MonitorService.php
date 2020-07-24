<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

class MonitorService
{
    public static function formatBytes(float $bytes, int $precision = 2, string $unit = null) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($unit) {
            $pow = array_search(strtoupper($unit), $units);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units: ".implode(', ', $units));
            }
        } else {
            $pow = min(floor(log($bytes) / log(1024)), count($units) - 1);
        }
        return round($bytes/pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    public function getSolrJvmOsMetrics($solrBase)
    {
        $jvm = json_decode(file_get_contents("$solrBase/admin/metrics?group=jvm&prefix=os"), true)['metrics']['solr.jvm'];

        return [
            'free_physical_memory' => (int) $jvm['os.freePhysicalMemorySize'],
            'total_physical_memory' => (int) $jvm['os.totalPhysicalMemorySize'],
            'used_physical_memory' => $jvm['os.totalPhysicalMemorySize'] - $jvm['os.freePhysicalMemorySize'],
            'free_physical_memory_human_readable' => self::formatBytes($jvm['os.freePhysicalMemorySize']),
            'total_physical_memory_human_readable' => self::formatBytes($jvm['os.totalPhysicalMemorySize']),
            'used_physical_memory_human_readable' => self::formatBytes($jvm['os.totalPhysicalMemorySize'] - $jvm['os.freePhysicalMemorySize']),
            'free_swap_space' => (int) $jvm['os.freeSwapSpaceSize'],
            'total_swap_space' => (int) $jvm['os.totalSwapSpaceSize'],
            'used_swap_space' => $jvm['os.totalSwapSpaceSize'] - $jvm['os.freeSwapSpaceSize'],
            'free_swap_space_human_readable' => self::formatBytes($jvm['os.freeSwapSpaceSize']),
            'total_swap_space_human_readable' => self::formatBytes($jvm['os.totalSwapSpaceSize']),
            'used_swap_space_human_readable' => self::formatBytes($jvm['os.totalSwapSpaceSize'] - $jvm['os.freeSwapSpaceSize']),
        ];
    }
}