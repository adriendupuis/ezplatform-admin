<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Service\ContentUsageService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentTypeUsage extends AbstractTab
{
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
        return 'ad-admin-content-type-usage-tab';
    }

    public function getName(): string
    {
        return /** @Desc("Content Type Usage Tab") */
            $this->translator->trans('content_type_usage', [], 'ad_admin_content_usage');
    }

    public function renderView(array $parameters): string
    {
        return $this->twig->render(
            '@ezdesign/tab/content_type_usage.html.twig',
            $this->contentUsageService->getContentTypeUsage()
        );
    }
}
