<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

class ContentUsageService
{
    /** @var Connection */
    private $dbalConnection;

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var FieldTypeService */
    private $fieldTypeService;

    /** @var SearchService */
    private $searchService;

    public function __construct(
        Connection $connection,
        ContentTypeService $contentTypeService,
        FieldTypeService $fieldTypeService,
        SearchService $searchService
    ) {
        $this->dbalConnection = $connection;
        $this->contentTypeService = $contentTypeService;
        $this->fieldTypeService = $fieldTypeService;
        $this->searchService = $searchService;
    }

    public function getContentTypeUsage()
    {
        /** @var array[] $contentCountList */
        $contentCountList = $this->dbalConnection->createQueryBuilder()
            ->select('c.identifier AS content_type_identifier', 'COUNT(o.id) AS content_count')
            ->from('ezcontentclass', 'c')
            ->leftJoin('c', 'ezcontentobject', 'o', 'c.id = o.contentclass_id')
            ->where('1 = o.status')// to count published content
            ->orWhere('o.id IS NULL')// to count unused content type
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

    public function findExamples(ContentType $contentType, int $limit = 25, int $offset = 0)
    {
        $searchAllExamples = false;
        if (-1 === $limit) {
            $searchAllExamples = true;
            $offset = 0;
            $limit = 0;
        }

        $searchResult = $this->searchService->findContent(new Query([
            'filter' => new Query\Criterion\ContentTypeId($contentType->id),
            'offset' => $offset,
            'limit' => $limit,
        ]));

        if ($searchAllExamples && $searchResult->totalCount) {
            $searchResult = $this->searchService->findContent(new Query([
                'filter' => new Query\Criterion\ContentTypeId($contentType->id),
                'offset' => $offset,
                'limit' => $searchResult->totalCount,
            ]));
        }

        $examples = [];

        foreach ($searchResult->searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $bestExampleScore = 0;
            $goodExampleFieldDefIdentifierList = [];
            $worstExampleScore = 0;
            $badExampleFieldDefIdentifierList = [];

            foreach ($content->getFields() as $field) {
                if (\in_array($field->fieldTypeIdentifier, $this->neverEmptyFieldTypeIdentifierList)) {
                    continue;
                }
                $isRequired = $contentType->getFieldDefinition($field->fieldDefIdentifier)->isRequired;
                $isEmpty = $this->fieldTypeService->getFieldType($field->fieldTypeIdentifier)->isEmptyValue($field->value);
                if ($isRequired && $isEmpty) {
                    // Bad example
                    ++$worstExampleScore;
                    $badExampleFieldDefIdentifierList[] = $field->fieldDefIdentifier;
                } elseif (!$isRequired && !$isEmpty) {
                    // Good example
                    ++$bestExampleScore;
                    $goodExampleFieldDefIdentifierList[] = $field->fieldDefIdentifier;
                }
            }

            $exampleData = [
                'score' => $worstExampleScore ? $worstExampleScore : $bestExampleScore,
                'name' => $content->getName(),
                //'id' => $content->id,
                'url' => $this->generateUrl('_ez_content_view', ['contentId' => $content->id]),
                //'url_alias' => $this->generateUrl('ez_urlalias', [ 'contentId' => $content->id ]),
            ];

            if ($worstExampleScore) {
                // Bad example
                foreach ($badExampleFieldDefIdentifierList as $fieldDefIdentifier) {
                    if (!\array_key_exists($fieldDefIdentifier, $examples)) {
                        $examples[$fieldDefIdentifier] = ['bads' => []];
                    }
                    $examples[$fieldDefIdentifier]['bads'][] = $exampleData;
                }
            } /* a bad example can't be a good one */ elseif ($bestExampleScore) {
                // Good example
                foreach ($goodExampleFieldDefIdentifierList as $fieldDefIdentifier) {
                    if (!\array_key_exists($fieldDefIdentifier, $examples)) {
                        $examples[$fieldDefIdentifier] = [];
                    }
                    if (!\array_key_exists('good', $examples[$fieldDefIdentifier]) || $bestExampleScore > $examples[$fieldDefIdentifier]['good']['score']) {
                        $examples[$fieldDefIdentifier]['good'] = $exampleData;
                    }
                }
            }
        }

        return [
            'totalCount' => $searchResult->totalCount,
            'examples' => $examples,
        ];
    }
}
