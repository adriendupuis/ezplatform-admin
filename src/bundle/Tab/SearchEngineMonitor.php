<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\ElasticsearchMonitorService;
use AdrienDupuis\EzPlatformAdminBundle\Service\SearchEngineMonitorServiceAbstract;
use AdrienDupuis\EzPlatformAdminBundle\Service\SolrMonitorService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SearchEngineMonitor extends AbstractTab
{
    public const IDENTIFIER = 'ad-admin-monitor-search-engine-tab';

    /** @var string */
    private $searchEngine;

    /** @var SearchEngineMonitorServiceAbstract|null */
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

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /** @Desc("%search_engine_name% Monitor") */
            $this->translator->trans('monitor.tab_name', [
                '%search_engine_name%' => $this->getSearchEngineName($this->searchEngine),
            ], 'ad_admin_monitor');
    }

    public function renderView(array $parameters): string
    {
        if (in_array($this->searchEngine, self::getSupportedSearchEngines(), true)) {
            if ($this->searchEngineMonitorService->ping()) {
                return $this->twig->render('@ezdesign/tab/search_engine_monitor.html.twig', [
                    'os_metrics' => $this->searchEngineMonitorService->getOsMetrics(),
                ]);
            }

            return 'Search engine does not respond';
        }

        return  '(Not monitored)';
    }

    public static function getSupportedSearchEngines(): array
    {
        return ['solr', 'elasticsearch'];
    }

    public function getSearchEngineName($identifier): string
    {
        return ucfirst($this->searchEngine);
    }
}
