<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab\Monitor;

use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\ElasticsearchMonitorService;
use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\ServerMonitorServiceAbstract;
use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\SolrMonitorService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use EzSystems\EzPlatformAdminUi\Tab\ConditionalTabInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SearchEngineMonitor extends AbstractTab implements ConditionalTabInterface
{
    public const IDENTIFIER = 'ad-admin-monitor-search-engine-tab';

    /** @var string */
    private $searchEngine;

    /** @var ServerMonitorServiceAbstract|null */
    private $searchEngineMonitorService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        string $searchEngine,
        SolrMonitorService $solrMonitorService,
        ElasticsearchMonitorService $elasticsearchMonitorService
    ) {
        parent::__construct($twig, $translator);
        $this->searchEngine = $searchEngine;
        switch ($this->searchEngine) {
            case 'solr':
                $this->searchEngineMonitorService = $solrMonitorService;
                break;
            case 'elasticsearch':
                $this->searchEngineMonitorService = $elasticsearchMonitorService;
                break;
        }
    }

    public static function getSupportedSearchEngines(): array
    {
        return ['solr', 'elasticsearch'];
    }

    public function evaluate(array $parameters): bool
    {
        return in_array($this->searchEngine, self::getSupportedSearchEngines(), true);
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /** @Desc("%service_name% Monitor") */
            $this->translator->trans('monitor.tab_name', [
                '%service_name%' => ucfirst($this->searchEngine),
            ], 'ad_admin_monitor');
    }

    public function renderView(array $parameters): string
    {
        if (in_array($this->searchEngine, self::getSupportedSearchEngines(), true)) {
            if ($this->searchEngineMonitorService->ping()) {
                return $this->twig->render('@ezdesign/tab/server_monitor.html.twig', [
                    'os_metrics' => $this->searchEngineMonitorService->getMetrics(),
                ]);
            }

            return /** @Desc("Search engine does not respond") */
                $this->translator->trans('monitor.search_engine.no_ping', [
                ], 'ad_admin_monitor');
        }

        return /** @Desc("(Not monitored)") */
            $this->translator->trans('monitor.not_monitored', [
            ], 'ad_admin_monitor');
    }
}
