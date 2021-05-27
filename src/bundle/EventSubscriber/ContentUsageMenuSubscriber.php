<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentUsageMenuSubscriber implements EventSubscriberInterface, TranslationContainerInterface
{
    public const ITEM_CONTENT__CONTENT_USAGE = 'main__content__content_usage';

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
        $menuItem = $menu[MainMenuBuilder::ITEM_CONTENT];

        $menuItem->addChild(
            self::ITEM_CONTENT__CONTENT_USAGE,
            [
                'route' => 'ad_admin.content_usage',
                'extras' => [
                    'translation_domain' => 'ad_admin_content_usage',
                ],
            ]
        );
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM_CONTENT__CONTENT_USAGE, 'ad_admin_content_usage'))->setDesc('Usage'),
        ];
    }
}
