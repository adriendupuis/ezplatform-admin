<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventSubscriber;

use AdrienDupuis\EzPlatformAdminBundle\Tab\SearchEngineMonitor;
use EzSystems\EzPlatformAdminUi\Tab\Event\TabEvents;
use EzSystems\EzPlatformAdminUi\Tab\Event\TabGroupEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MonitorTabGroupSubscriber implements EventSubscriberInterface
{
    /** @var string */
    public $searchEngine;

    public function __construct(string $searchEngine)
    {
        $this->searchEngine = $searchEngine;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TabEvents::TAB_GROUP_PRE_RENDER => ['onTabGroupPreRender', 10],
        ];
    }

    public function onTabGroupPreRender(TabGroupEvent $event): void
    {
        $tabGroup = $event->getData();

        if ('ad-admin-monitor' !== $tabGroup->getIdentifier()) {
            return;
        }

        if (!in_array($this->searchEngine, SearchEngineMonitor::getSupportedSearchEngines())) {
            $tabGroup->removeTab(SearchEngineMonitor::IDENTIFIER);
        }
    }
}
