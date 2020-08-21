<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\RedisMonitorService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RedisMonitor extends AbstractTab
{
    public const IDENTIFIER = 'ad-admin-monitor-redis-tab';

    /** @var RedisMonitorService */
    private $redisMonitorService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        RedisMonitorService $redisMonitorService
    ) {
        parent::__construct($twig, $translator);
        $this->redisMonitorService = $redisMonitorService;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /** @Desc("Redis Monitor") */
            $this->translator->trans('monitor.redis', [
            ], 'ad_admin_monitor');
    }

    public function renderView(array $parameters): string
    {
        if ($this->redisMonitorService->ping()) {
            return $this->twig->render('@ezdesign/tab/server_monitor.html.twig', [
                'os_metrics' => $this->redisMonitorService->getOsMetrics(),
            ]);
        }

        return /** @Desc("Redis does not respond") */
            $this->translator->trans('monitor.redis.no_ping', [
            ], 'ad_admin_monitor');
    }
}