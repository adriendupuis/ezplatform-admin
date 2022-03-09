<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab\Monitor;

use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\MemcachedMonitorService;
use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\RedisMonitorService;
use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\ServerMonitorServiceAbstract;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use EzSystems\EzPlatformAdminUi\Tab\ConditionalTabInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CachePoolMonitor extends AbstractTab implements ConditionalTabInterface
{
    public const IDENTIFIER = 'ad-admin-monitor-cache-pool-tab';

    /** @var string */
    private $cachePool;

    /** @var ServerMonitorServiceAbstract|null */
    private $cachePoolMonitorService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        string $cachePool,
        RedisMonitorService $redisMonitorService,
        MemcachedMonitorService $memcachedMonitorService
    ) {
        parent::__construct($twig, $translator);
        $this->cachePool = str_replace('cache.', '', $cachePool);
        switch ($this->cachePool) {
            case 'redis':
                $this->cachePoolMonitorService = $redisMonitorService;
                break;
            case 'memcached':
                $this->cachePoolMonitorService = $memcachedMonitorService;
                break;
        }
    }

    public static function getSupportedCachePools(): array
    {
        return ['redis', 'memcached'];
    }

    public function evaluate(array $parameters): bool
    {
        return in_array($this->cachePool, self::getSupportedCachePools(), true);
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /** @Desc("%service_name% Monitor") */
            $this->translator->trans('monitor.tab_name', [
                '%service_name%' => ucfirst($this->cachePool),
            ], 'ad_admin_monitor');
    }

    public function renderView(array $parameters): string
    {
        if ($this->cachePoolMonitorService->ping()) {
            return $this->twig->render('@ezdesign/tab/server_monitor.html.twig', [
                'server_monitor_addtition_template' => "@ezdesign/tab/{$this->cachePool}_monitor.html.twig",
                'os_metrics' => $this->cachePoolMonitorService->getMetrics(),
            ]);
        }

        return /** @Desc("Cache pool does not respond") */
            $this->translator->trans('monitor.cache_pool.no_ping', [
            ], 'ad_admin_monitor');
    }
}
