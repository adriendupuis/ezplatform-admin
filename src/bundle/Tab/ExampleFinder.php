<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use eZ\Publish\API\Repository\ContentTypeService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ExampleFinder extends AbstractTab
{
    /** @var ContentTypeService */
    private $contentTypeService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        ContentTypeService $contentTypeService
    ) {
        parent::__construct($twig, $translator);

        $this->contentTypeService = $contentTypeService;
    }

    public function getIdentifier(): string
    {
        return 'ad-admin-example-finder-tab';
    }

    public function getName(): string
    {
        return /* @Desc("Example Finder Tab") */
            $this->translator->trans('example_finder', [], 'ad_admin.content_usage');
    }

    public function renderView(array $parameters): string
    {
        $contentTypeGroups = $this->contentTypeService->loadContentTypeGroups();
        foreach ($contentTypeGroups as $contentTypeGroup) {
            $contentTypeList[$contentTypeGroup->id] = [
                'itself' => $contentTypeGroup,
                'content_types' => $this->contentTypeService->loadContentTypes($contentTypeGroup),
            ];
        }

        return $this->twig->render('@ezdesign/tab/example_finder.html.twig', [
            'content_type_list' => $contentTypeList ?? [],
        ]);
    }
}
