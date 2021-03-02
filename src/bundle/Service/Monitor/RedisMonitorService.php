<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service\Monitor;

use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\DeflateMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\Cache\Marshaller\TagAwareMarshaller;
use Symfony\Component\Cache\Traits\RedisClusterProxy;
use Symfony\Component\Cache\Traits\RedisProxy;
use Symfony\Component\Cache\Traits\RedisTrait;

/**
 * @todo Monitor Redis when used as session handler
 * @todo Predis
 */
class RedisMonitorService extends ServerMonitorServiceAbstract
{
    use RedisTrait;

    /**
     * It seems that service containing the Redis clients (RedisAdapter, RedisTagAwareAdapter, '@cache.redis.recorder_inner',…)
     * have private methods that disollow access to hosts, ping, info and such functions
     */
    public function __construct(string $cachePool, string $cacheDsn)
    {
        $this->redis = new \Redis();
        if ('cache.redis' === $cachePool) {
            $this->redis->connect($cacheDsn);
        }
        $this->marshaller = new DefaultMarshaller();
    }

    public function ping(): bool
    {
        foreach ($this->getHosts() as $host) {
            try {
                // https://redis.io/commands/ping
                if ('+PONG' !== $host->ping()) {
                    return false;
                }
            } catch (\Throwable $throwable) {
                return false;
            }
        }

        return true;
    }

    public function getMetrics(): array
    {
        $metrics = [];

        /** @var \Redis $host */
        foreach ($this->getHosts() as $host) {
            $node = $host->getHost().($host->getPort() ? ":{$host->getPort()}" : '');

            $info = array_merge($host->info('MEMORY'), $host->info('STATS'));

            $freePhysicalMemory = $info['total_system_memory'] - $info['used_memory_rss'];
            $hitRatio = $info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']);
            $metrics[$node] = [
                'free_physical_memory' => $freePhysicalMemory,
                'total_physical_memory' => (int) $info['total_system_memory'],
                'used_physical_memory' => (int) $info['used_memory_rss'],
                'free_physical_memory_human' => self::formatBytes($freePhysicalMemory),
                'total_physical_memory_human' => self::formatBytes($info['total_system_memory']),
                'used_physical_memory_human' => self::formatBytes($info['used_memory_rss']),
                'free_physical_memory_percent' => self::formatPercent($freePhysicalMemory/$info['total_system_memory']),
                'used_physical_memory_percent' => self::formatPercent($info['used_memory_rss']/$info['total_system_memory']),
                'mem_fragmentation_ratio' => (float) $info['mem_fragmentation_ratio'],

                'hits' => $info['keyspace_hits'],
                'misses' => $info['keyspace_misses'],
                'hit_ratio' => $hitRatio,
                'hit_ratio_percent' => self::formatPercent($hitRatio),
                'evictions' => $info['evicted_keys'],
                'maxmemory_policy' => $info['maxmemory_policy'],
            ];
        }

        return $metrics;
    }
}
