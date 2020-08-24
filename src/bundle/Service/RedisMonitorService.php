<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\DeflateMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\Cache\Marshaller\TagAwareMarshaller;
use Symfony\Component\Cache\Traits\RedisClusterProxy;
use Symfony\Component\Cache\Traits\RedisProxy;
use Symfony\Component\Cache\Traits\RedisTrait;

class RedisMonitorService extends ServerMonitorServiceAbstract
{
    use RedisTrait;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\ClientInterface $redisClient
     */
    public function __construct(RedisProxy $redisClient, string $namespace = '', int $defaultLifetime = 0, MarshallerInterface $marshaller = null, ?string $redisDsn=null)
    {
        try {
            $redisClient->getHost();
        } catch (\Throwable $throwable) {
            dump('RedisMonitorService TODO: inject the right client', $redisClient, $throwable);
            $redisClient = new \Redis();
            $redisClient->connect($redisDsn);
        }

        if ($redisClient instanceof \Predis\ClientInterface && $redisClient->getConnection() instanceof ClusterInterface && !$redisClient->getConnection() instanceof PredisCluster) {
            throw new InvalidArgumentException(sprintf('Unsupported Predis cluster connection: only "%s" is, "%s" given.', PredisCluster::class, get_debug_type($redisClient->getConnection())));
        }

        if (\defined('Redis::OPT_COMPRESSION') && ($redisClient instanceof \Redis || $redisClient instanceof \RedisArray || $redisClient instanceof \RedisCluster)) {
            $compression = $redisClient->getOption(\Redis::OPT_COMPRESSION);

            foreach (\is_array($compression) ? $compression : [$compression] as $c) {
                if (\Redis::COMPRESSION_NONE !== $c) {
                    throw new InvalidArgumentException(sprintf('phpredis compression must be disabled when using "%s", use "%s" instead.', static::class, DeflateMarshaller::class));
                }
            }
        }

        // $this->init($redisClient, $namespace, $defaultLifetime, new TagAwareMarshaller($marshaller)); // “Cannot call constructor” RedisTrait.php:52

        if (preg_match('#[^-+_.A-Za-z0-9]#', $namespace, $match)) {
            throw new InvalidArgumentException(sprintf('RedisAdapter namespace contains "%s" but only characters in [-+_.A-Za-z0-9] are allowed.', $match[0]));
        }

        if (!$redisClient instanceof \Redis && !$redisClient instanceof \RedisArray && !$redisClient instanceof \RedisCluster && !$redisClient instanceof \Predis\ClientInterface && !$redisClient instanceof RedisProxy && !$redisClient instanceof RedisClusterProxy) {
            throw new InvalidArgumentException(sprintf('"%s()" expects parameter 1 to be Redis, RedisArray, RedisCluster or Predis\ClientInterface, "%s" given.', __METHOD__, get_debug_type($redisClient)));
        }

        if ($redisClient instanceof \Predis\ClientInterface && $redisClient->getOptions()->exceptions) {
            $options = clone $redisClient->getOptions();
            \Closure::bind(function () { $this->options['exceptions'] = false; }, $options, $options)();
            $redisClient = new $redisClient($redisClient->getConnection(), $options);
        }

        $this->redis = $redisClient;
        $this->marshaller = $marshaller ?? new DefaultMarshaller();
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

    public function getOsMetrics(): array
    {
        $metrics = [];
        /** @var \Redis $host */
        foreach ($this->getHosts() as $host) {
            $node = $host->getHost() . ($host->getPort() ? ":{$host->getPort()}" : '');

            $info = $host->info('Memory');
            $info = isset($info['Memory']) ? $info['Memory'] : $info;

            $metrics[$node] = [
                'free_physical_memory' => $info['total_system_memory'] - $info['used_memory_rss'],
                'total_physical_memory' => (int) $info['total_system_memory'],
                'used_physical_memory' => (int) $info['used_memory_rss'],
                'free_physical_memory_human' => self::formatBytes($info['total_system_memory'] - $info['used_memory_rss']),
                'total_physical_memory_human' => self::formatBytes($info['total_system_memory']),
                'used_physical_memory_human' => self::formatBytes($info['used_memory_rss']),

                'mem_fragmentation_ratio' => (float) $info['mem_fragmentation_ratio'],
                'maxmemory_policy' => $info['maxmemory_policy'],
            ];

            $info = $host->info('Stats');
            $info = isset($info['Stats']) ? $info['Stats'] : $info;

            $metrics[$node]['evicted_keys'] = $info['evicted_keys'];
        }


        return $metrics;
    }

    private function getRedisEvictionPolicy(): string
    {
        if (null !== $this->redisEvictionPolicy) {
            return $this->redisEvictionPolicy;
        }


        return $this->redisEvictionPolicy = '';
    }
}
