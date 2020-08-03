<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\ContentUsageService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LandingPageUsage extends AbstractTab
{
    public const IDENTIFIER = 'ad-admin-landing-page-usage-tab';

    /** @var ContentUsageService */
    private $contentUsageService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        ContentUsageService $contentUsageService
    ) {
        parent::__construct($twig, $translator);
        $this->contentUsageService = $contentUsageService;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getName(): string
    {
        return /* @Desc("Content Type Usage Tab") */
            $this->translator->trans('landing_page_usage', [], 'ad_admin_content_usage');
    }

    public function renderView(array $parameters): string
    {
        return $this->twig->render(
            '@ezdesign/tab/landing_page_usage.html.twig',
            array_merge(
                $this->contentUsageService->getLayoutUsage(),
                $this->contentUsageService->getBlockUsage(),
            )
        );
    }
}
