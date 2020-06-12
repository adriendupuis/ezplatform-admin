<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IntegrityService
{
    /** @var ContainerInterface */
    private $container;

    /** @var LanguageService */
    private $languageService;

    /** @var SiteAccess */
    private $siteAccess;

    public function __construct(ContainerInterface $container, SiteAccess $siteAccess)
    {
        $this->container = $container;
        $this->languageService = $this->container->get('ezpublish.api.service.language');
        $this->siteAccess = $siteAccess;
    }

    public function getAvailableAndMissingLanguages(): array
    {
        $configLanguageCodeList = $this->container->getParameter("ezsettings.{$this->siteAccess->name}.languages");
        $databaseLanguageList = $this->languageService->loadLanguages();

        $languageMap = [];
        $databaseLanguageCodeList = [];
        /** @var Language $language */
        foreach ($databaseLanguageList as $language) {
            $languageMap[$language->languageCode] = $language;
            $databaseLanguageCodeList[] = $language->languageCode;
        }

        return [
            'language_map' => $languageMap,
            'available_languages' => array_intersect($configLanguageCodeList, $databaseLanguageCodeList),
            'missing_languages' => [
                'from_config' => array_diff($databaseLanguageCodeList, $configLanguageCodeList), // Error: using this language(s) in admin can cause fatal errors
                'from_database' => array_diff($configLanguageCodeList, $databaseLanguageCodeList), // Notice: won't be usable until created in admin but no fatal error
            ],
        ];
    }
}