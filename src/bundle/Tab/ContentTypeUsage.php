<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use Doctrine\DBAL\Connection;
use eZ\Publish\API\Repository\ContentTypeService;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentTypeUsage extends AbstractTab
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
        return /* @Desc("Content Type Usage Tab") */
            $this->translator->trans('content_type_usage', [], 'ad_admin_content_usage');
    }

    public function renderView(array $parameters): string
    {
        /** @var array[] $contentCountList */
        $contentCountList = $this->dbalConnection->createQueryBuilder()
            ->select('c.identifier AS content_type_identifier', 'COUNT(o.id) AS content_count')
            ->from('ezcontentclass', 'c')
            ->leftJoin('c', 'ezcontentobject', 'o', 'c.id = o.contentclass_id')
            ->where('1 = o.status')
            ->orWhere('o.id IS NULL')
            ->groupBy('c.id')
            ->orderBy('content_count', 'DESC')
            ->execute()
            ->fetchAll()
        ;

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

        return $this->twig->render('@ezdesign/tab/content_type_usage.html.twig', [
            'global_content_count' => $globalContentCount,
            'used_content_type_count' => $usedContentTypeCount,
            'content_count_list' => $contentCountList,
        ]);
    }
}
