<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\MonitorService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SearchEngineMonitor extends AbstractTab
{
    public const IDENTIFIER = 'ad-admin-monitor-search-engine-tab';

    /** @var MonitorService */
    private $monitorService;

    /** @var string */
    private $searchEngine;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        MonitorService $monitorService,
        string $searchEngine
    ) {
        parent::__construct($twig, $translator);
        $this->monitorService = $monitorService;
        $this->searchEngine = $searchEngine;
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
        switch ($this->searchEngine) {
            case 'solr':
                return $this->twig->render('@ezdesign/tab/solr_monitor.html.twig', $this->monitorService->getSolrJvmOsMetrics());
            case 'elasticsearch':
                return '(Not yet implemented)'; //TODO
            case 'legacy':
            default:
                return '(Not monitored)';
        }
    }

    public static function getSupportedSearchEngines(): array
    {
        return ['solr'/*TODO: , 'elasticsearch'*/];
    }

    public function getSearchEngineName($identifier): string
    {
        $nameMap = [
            'elasticsearch' => 'Elastic Search',
        ];

        if (array_key_exists($identifier, $nameMap)) {
            return $nameMap[$identifier];
        }

        return ucfirst($this->searchEngine);
    }
}
