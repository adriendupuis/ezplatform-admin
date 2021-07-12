<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service\Monitor;

use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class MemcachedMonitorService extends ServerMonitorServiceAbstract
{
    /** @var \Memcached */
    private $memcached;

    public function __construct(string $cachePool, string $cacheDsn)
    {
        if ('cache.memcached' === $cachePool) {
            $this->memcached = MemcachedAdapter::createConnection("memcached://$cacheDsn");
        }
    }

    public function ping(): bool
    {
        return false !== $this->memcached->getVersion();
    }

    public function getMetrics(): array
    {
        $metrics = [];

        /**
         * @var string $host
         * @var array  $stats
         */
        foreach ($this->memcached->getStats() as $host => $stats) {
            $freePhysicalMemory = $stats['limit_maxbytes'] - $stats['bytes'];
            $hitRatio = $stats['get_hits'] / ($stats['get_hits'] + $stats['get_misses']);
            $metrics[$host] = [
                'free_physical_memory' => $freePhysicalMemory,
                'total_physical_memory' => (int) $stats['limit_maxbytes'],
                'used_physical_memory' => (int) $stats['bytes'],
                'free_physical_memory_human' => self::formatBytes($freePhysicalMemory),
                'total_physical_memory_human' => self::formatBytes($stats['limit_maxbytes']),
                'used_physical_memory_human' => self::formatBytes($stats['bytes']),
                'free_physical_memory_percent' => self::formatPercent($freePhysicalMemory / $stats['limit_maxbytes']),
                'used_physical_memory_percent' => self::formatPercent($stats['bytes'] / $stats['limit_maxbytes']),

                'hits' => (int) $stats['get_hits'],
                'misses' => (int) $stats['get_misses'],
                'hit_ratio' => $hitRatio,
                'hit_ratio_percent' => self::formatPercent($hitRatio),
                'evictions' => $stats['evictions'],
            ];
        }

        return $metrics;
    }
}
