<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
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

    /** @var Connection */
    private $dbalConnection;

    public function __construct(ContainerInterface $container, SiteAccess $siteAccess, Connection $connection/*, LanguageService, $languageService*/)
    {
        $this->container = $container;
        $this->languageService = $this->container->get('ezpublish.api.service.language');
        $this->siteAccess = $siteAccess;
        //$this->dbalConnection = $this->container->get('doctrine.dbal.connection'); // The "doctrine.dbal.connection" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.
        $this->dbalConnection = $connection;
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

    public function getUnknownLanguages(): array
    {
        $availableLanguageMask = $this->dbalConnection->createQueryBuilder()
            ->select('BIT_OR(l.id) AS available_language_mask')
            ->from('ezcontent_language', 'l')
            ->execute()
            ->fetchColumn(0)
        ;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->dbalConnection->createQueryBuilder();
        return $queryBuilder
            ->select(["DISTINCT (o.language_mask ^ $availableLanguageMask) AS unknown_language"])
            ->from('ezcontentobject', 'o')
            ->leftJoin('o', 'ezcontent_language', 'l', 'o.language_mask & l.id = l.id')
            ->where($queryBuilder->expr()->isNull('l.id'))
            ->execute()
            ->fetchAll()
        ;
    }
}
