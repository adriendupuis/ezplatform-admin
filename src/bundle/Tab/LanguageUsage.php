<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LanguageUsage extends AbstractTab
{
    /** @var LanguageService */
    private $languageService;

    /** @var SiteAccess */
    private $siteAccess;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        ContainerInterface $container
    ) {
        parent::__construct($twig, $translator);
        $this->container = $container;
        $this->languageService = $this->container->get('ezpublish.api.service.language');
    }

    public function getIdentifier(): string
    {
        return 'ad-admin-language-usage-tab';
    }

    public function getName(): string
    {
        return /* @Desc("Language Usage Tab") */
            $this->translator->trans('language_usage', [], 'ad_admin_content_usage');
    }

    public function setSiteAccess(SiteAccess $siteAccess): void
    {
        $this->siteAccess = $siteAccess;
    }

    public function renderView(array $parameters): string
    {
        $configLanguageCodeList = $this->container->getParameter("ezsettings.{$this->siteAccess->name}.languages");
        $databaseLanguageList = $this->languageService->loadLanguages();
        $languageMap = [];
        $databaseLanguageCodeList = [];
        /** @var Language $language */
        foreach ($databaseLanguageList as $language) {
            $databaseLanguageCodeList[] = $language->languageCode;
            $languageMap[$language->languageCode] = $language;
        }
        //TODO: language usage stats

        return $this->twig->render(
            '@ezdesign/tab/language_usage.html.twig',
            [
                'language_map' => $languageMap,
                'language_code_list' => array_intersect($configLanguageCodeList, $databaseLanguageCodeList),
                'missing_languages' => [
                    'from_config' => array_diff($databaseLanguageCodeList, $configLanguageCodeList), // Error: using this language(s) in admin can cause errors
                    'from_database' => array_diff($configLanguageCodeList, $databaseLanguageCodeList), // Notice: won't be usable until created in admin
                ],
            ]
        );
    }
}
