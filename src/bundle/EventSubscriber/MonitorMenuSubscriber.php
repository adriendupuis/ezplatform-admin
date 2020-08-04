<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MonitorMenuSubscriber implements EventSubscriberInterface, TranslationContainerInterface
{
    const ITEM_ADMIN__MONITOR = 'main__admin__monitor';

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => 'onMenuConfigure',
        ];
    }

    public function onMenuConfigure(ConfigureMenuEvent $event): void
    {
        /** @var MenuItem $menu */
        $menu = $event->getMenu();
        /** @var MenuItem $menuItem */
        $menuItem = $menu[MainMenuBuilder::ITEM_ADMIN];

        $menuItem->addChild(
            self::ITEM_ADMIN__MONITOR,
            [
                'route' => 'ad_admin.monitor',
                'extras' => [
                    'translation_domain' => 'ad_admin_monitor',
                ],
            ]
        );
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM_ADMIN__MONITOR, 'ad_admin_monitor'))->setDesc('Monitor'),
        ];
    }
}
