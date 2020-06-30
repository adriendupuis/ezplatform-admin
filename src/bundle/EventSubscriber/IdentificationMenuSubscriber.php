<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IdentificationMenuSubscriber implements EventSubscriberInterface, TranslationContainerInterface
{
    const ITEM_ADMIN__IDENTIFICATION = 'main__admin__identification';

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
            self::ITEM_ADMIN__IDENTIFICATION,
            [
                'route' => 'ad_admin.identification',
                'extras' => [
                    'translation_domain' => 'ad_admin_identification',
                    'orderNumber' => 666, //TODO
                ],
            ]
        );
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM_ADMIN__IDENTIFICATION, 'ad_admin_identification'))->setDesc('Identification'),
        ];
    }
}
