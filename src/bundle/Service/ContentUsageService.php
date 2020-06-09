<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;

class ContentUsageService
{
    /** @var Connection */
    private $dbalConnection;

    /** @var ContentTypeService */
    private $contentTypeService;

    function __construct(
        Connection $connection,
        ContentTypeService $contentTypeService,
        SearchService $searchService
    )
    {
        $this->dbalConnection = $connection;
        $this->contentTypeService = $contentTypeService;
    }

    public function getContentTypeUsage()
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

        return [
            'global_content_count' => $globalContentCount,
            'used_content_type_count' => $usedContentTypeCount,
            'content_count_list' => $contentCountList,
        ];
    }

    //TODO: public function findExample(string $contentType, int $offset=0, int $limit=25)
}