<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventSubscriber;

use AdrienDupuis\EzPlatformAdminBundle\Tab\LandingPageUsage;
use EzSystems\EzPlatformAdminUi\Tab\Event\TabEvents;
use EzSystems\EzPlatformAdminUi\Tab\Event\TabGroupEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentUsageTabGroupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TabEvents::TAB_GROUP_PRE_RENDER => ['onTabGroupPreRender', 10],
        ];
    }

    public function onTabGroupPreRender(TabGroupEvent $event): void
    {
        $tabGroup = $event->getData();

        if ('ad-admin-content-usage' !== $tabGroup->getIdentifier()) {
            return;
        }

        // Remove tab of uninstalled feature
        if (!class_exists('EzSystems\EzPlatformPageFieldTypeBundle\EzPlatformPageFieldTypeBundle')) {
            // Enterprise Edition feature
            $tabGroup->removeTab(LandingPageUsage::IDENTIFIER);
        }
    }
}