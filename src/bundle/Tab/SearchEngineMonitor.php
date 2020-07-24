<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\MonitorService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SearchEngineMonitor extends AbstractTab
{
    /** @var MonitorService */
    private $monitorService;

    /** @var string */
    private $searchEngine;

    /** @var string */
    private $searchDsn;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        MonitorService $monitorService,
        string $searchEngine,
        string $solrDsn,
        string $elasticsearchDsn
    ) {
        parent::__construct($twig, $translator);
        $this->monitorService = $monitorService;
        $this->searchEngine = $searchEngine;
        switch($this->searchEngine) {
            case 'solr':
                $this->searchDsn = $solrDsn;
                break;
            case 'elasticsearch':
                $this->searchDsn = $elasticsearchDsn;
        }
    }
    public function getIdentifier(): string
    {
        return 'ad-admin-monitor-search-engine-tab';
    }

    public function getName(): string
    {
        return ucfirst($this->searchEngine) . ' Monitor';
    }

    public function renderView(array $parameters): string
    {
        switch($this->searchEngine) {
            case 'solr':
                return $this->twig->render('@ezdesign/tab/solr_monitor.html.twig', $this->monitorService->getSolrJvmOsMetrics($this->searchDsn));;
            case 'legacy':
            default:
                return '(Not monitored)';
        }
    }
}