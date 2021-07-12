<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab\Monitor;

use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\HostMonitorService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CurrentHostMonitor extends AbstractTab
{
    public const IDENTIFIER = 'ad-admin-monitor-current-host-tab';

    /** @var HostMonitorService */
    private $hostMonitorService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        HostMonitorService $hostMonitorService
    ) {
        parent::__construct($twig, $translator);
        $this->hostMonitorService = $hostMonitorService;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /** @Desc("Current Host Monitor") */
            $this->translator->trans('monitor.current_host', [
            ], 'ad_admin_monitor');
    }

    public function renderView(array $parameters): string
    {
        return $this->twig->render('@ezdesign/tab/server_monitor.html.twig', [
            'os_metrics' => $this->hostMonitorService->getMetrics(),
        ]);
    }
}
