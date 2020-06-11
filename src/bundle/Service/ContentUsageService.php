<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Symfony\Component\Routing\RouterInterface;

class ContentUsageService
{
    /**
     * Even if optional, by their own logic, those field types can't be empty.
     *
     * @var string[]
     */
    public $neverEmptyFieldTypeIdentifierList = [
        'ezauthor',
        'ezboolean',
        //TODO: Check all build-in field types
    ];

    /** @var Connection */
    private $dbalConnection;

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var FieldTypeService */
    private $fieldTypeService;

    /** @var SearchService */
    private $searchService;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        Connection $connection,
        ContentTypeService $contentTypeService,
        FieldTypeService $fieldTypeService,
        SearchService $searchService,
        RouterInterface $router
    ) {
        $this->dbalConnection = $connection;
        $this->contentTypeService = $contentTypeService;
        $this->fieldTypeService = $fieldTypeService;
        $this->searchService = $searchService;
        $this->router = $router;
    }

    public function getContentTypeUsage(): array
    {
        /** @var array[] $contentCountList */
        $contentCountList = $this->dbalConnection->createQueryBuilder()
            ->select('c.identifier AS content_type_identifier', 'COUNT(o.id) AS content_count')
            ->from('ezcontentclass', 'c')
            ->leftJoin('c', 'ezcontentobject', 'o', 'c.id = o.contentclass_id')
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

    public function findExamples(ContentType $contentType, int $limit = 25, int $offset = 0): array
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
                'score' => $worstExampleScore ?: $bestExampleScore,
                'name' => $content->getName(),
                'url' => $this->router->generate('_ez_content_view', ['contentId' => $content->id]),
                //'url_alias' => $this->router->generate('ez_urlalias', ['contentId' => $content->id]),
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

    public function getLanguageUsage(): array
    {
        return $this->dbalConnection->query(<<<SQL
SELECT ezcontent_language.locale AS language_code, COUNT(ezcontentobject.id) AS content_count
  FROM ezcontentobject
    LEFT JOIN ezcontent_language ON ezcontentobject.language_mask & ezcontent_language.id = ezcontent_language.id
  GROUP BY ezcontent_language.locale
  ORDER BY content_count DESC
;
SQL)->fetchAll();
    }
}
