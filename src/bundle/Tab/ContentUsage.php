<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use Doctrine\DBAL\Driver\Connection;
use eZ\Publish\API\Repository\ContentTypeService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentUsage extends AbstractTab
{
    /** @var Connection */
    private $dbalConnection;

    /** @var ContentTypeService */
    private $contentTypeService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        Connection $connection,
        ContentTypeService $contentTypeService
    ) {
        parent::__construct($twig, $translator);

        $this->dbalConnection = $connection;
        $this->contentTypeService = $contentTypeService;
    }

    public function getIdentifier(): string
    {
        return 'ad-admin-content-usage-tab';
    }

    public function getName(): string
    {
        return /* @Desc("Content Usage Tab") */
            $this->translator->trans('content_usage', [], 'ad_admin.content_usage');
    }

    public function renderView(array $parameters): string
    {
        /** @var array[] $contentCountList */
        $contentCountList = $this->dbalConnection->query(<<<SQL
SELECT ezcontentclass.identifier AS content_type_identifier, COUNT(ezcontentobject.id) AS content_count
  FROM ezcontentclass
    LEFT JOIN ezcontentobject ON ezcontentclass.id = ezcontentobject.contentclass_id
  WHERE 1 = ezcontentobject.status
    OR ezcontentobject.id IS NULL
  GROUP BY ezcontentclass.id
  ORDER BY content_count DESC
;
SQL)->fetchAll();

        $globalContentCount = 0;
        $usedContentTypeCount = 0;
        foreach ($contentCountList as &$contentCount) {
            $contentCount['content_type'] = $this->contentTypeService->loadContentTypeByIdentifier($contentCount['content_type_identifier']);
            $contentCount['content_type_group'] = $contentCount['content_type']->getContentTypeGroups()[0];
            if ($contentCount['content_count']) {
                $globalContentCount += $contentCount['content_count'];
                ++$usedContentTypeCount;
            }
        }

        return $this->twig->render('@ezdesign/tab/content_usage.html.twig', [
            'global_content_count' => $globalContentCount,
            'used_content_type_count' => $usedContentTypeCount,
            'content_count_list' => $contentCountList,
        ]);
    }
}
