<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\ContentUsageService;
use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use eZ\Publish\API\Repository\Values\Content\Language;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LanguageUsage extends AbstractTab
{
    /** @var ContentUsageService */
    private $contentUsageService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        ContentUsageService $contentUsageService,
        IntegrityService $integrityService
    ) {
        parent::__construct($twig, $translator);
        $this->contentUsageService = $contentUsageService;
        $this->integrityService = $integrityService;
    }

    public function getIdentifier(): string
    {
        return 'ad-admin-language-usage-tab';
    }

    public function getName(): string
    {
        return /** @Desc("Language Usage Tab") */
            $this->translator->trans('language_usage', [], 'ad_admin_content_usage');
    }

    public function renderView(array $parameters): string
    {
        $languageUsage = $this->integrityService->getAvailableAndMissingLanguages();
        $languageUsage['content_count_list'] = $this->contentUsageService->getLanguageUsage();
        $unusedLanguage = array_diff($languageUsage['available_languages'], array_keys($languageUsage['content_count_list']));
        $languageUsage['content_count_list'] = array_merge(
            $languageUsage['content_count_list'],
            array_combine($unusedLanguage, array_fill(0, count($unusedLanguage), 0))
        );

        return $this->twig->render('@ezdesign/tab/language_usage.html.twig', $languageUsage);
    }
}
