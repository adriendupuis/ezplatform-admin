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
            ->select(['c.identifier AS content_type_identifier', 'COUNT(o.id) AS content_count'])
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

    /**
     * Search good (best) example and bad examples for each field of a content type.
     *
     * An optional field's good example is having a value (non empty).
     * An optional field's best example is the good one having the most other optional fields with values.
     * A mandatory field's bad example is missing a value.
     *
     * A bad example can't be a good example even if it has a nice quantity of optional fields with values.
     *
     * A good example's score is the count of optional fields with a value.
     * A bad example's score is the count of mandatory fields without a value.
     *
     * Return an array with
     * - the total count of content object of this content type,
     * - one good example which the best example for the studied slice
     * - an array of bad examples
     *
     * @param ContentType $contentType  the content type to find examples for
     * @param int         $limit        number of contents to study; If less than zero, all contents from $offset
     * @param int         $offset       number of contents to skip before studying; $limit and $offset can be used to slice the study of a great quantity of contents
     * @param string|null $languageCode the code of the language in which studying; if not given, in all languages where a field isn't empty if one of the translations have a value for it
     *
     * @return array ['totalCount' => <int>, 'examples' => ['good' => ['score' => <int>, 'id' => <int>, 'name' => '<string>', 'url' => '<string>'], 'bad' => [['score' => <int>, 'id' => <int>, …], …]]]
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function findExamples(ContentType $contentType, int $limit = 25, int $offset = 0, ?string $languageCode = null): array
    {
        $searchAllExamples = false;
        if (0 > $limit) {
            $searchAllExamples = true;
            $limit = 0;
        }

        $filter = new Query\Criterion\ContentTypeId($contentType->id);
        if ($languageCode) {
            $filter = new Query\Criterion\LogicalAnd([
                $filter,
                new Query\Criterion\LanguageCode($languageCode, false),
            ]);
        }

        $searchResult = $this->searchService->findContent(new Query([
            'filter' => $filter,
            'offset' => $offset,
            'limit' => $limit,
        ]));

        if ($searchAllExamples && $searchResult->totalCount) {
            $searchResult = $this->searchService->findContent(new Query([
                'filter' => $filter,
                'offset' => $offset,
                'limit' => $searchResult->totalCount,
            ]));
        }

        $examples = [];
        $fieldUsage = [];

        foreach ($searchResult->searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $bestExampleScore = 0;
            $goodExampleFieldDefIdentifierList = [];
            $worstExampleScore = 0;
            $badExampleFieldDefIdentifierList = [];

            foreach ($languageCode ? $content->getFieldsByLanguage($languageCode) : $content->getFields() as $field) {
                if (\in_array($field->fieldTypeIdentifier, $this->neverEmptyFieldTypeIdentifierList)) {
                    continue;
                }
                if (!\array_key_exists($field->fieldDefIdentifier, $fieldUsage)) {
                    $fieldUsage[$field->fieldDefIdentifier] = 0;
                }
                $isRequired = $contentType->getFieldDefinition($field->fieldDefIdentifier)->isRequired;
                $isEmpty = $this->fieldTypeService->getFieldType($field->fieldTypeIdentifier)->isEmptyValue($field->value);
                $fieldUsage[$field->fieldDefIdentifier] += $isEmpty ? 0 : 1;
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
                'id' => $content->id,
                'name' => $content->getName(),
                'url' => $this->router->generate('_ez_content_view', ['contentId' => $content->id]),
                //'urlAlias' => $this->router->generate('ez_urlalias', ['contentId' => $content->id]),
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
            'sliceCount' => count($searchResult->searchHits),
            'totalCount' => $searchResult->totalCount,
            'examples' => $examples,
            'fieldUsage' => $fieldUsage,
        ];
    }

    public function getLanguageUsage(): array
    {
        return $this->dbalConnection->createQueryBuilder()
            ->select(['l.locale AS language_code', 'COUNT(o.id) AS content_count'])
            ->from('ezcontentobject', 'o')
            ->leftJoin('o', 'ezcontent_language', 'l', 'o.language_mask & l.id = l.id')
            ->groupBy('l.id')
            ->orderBy('content_count', 'DESC')
            ->execute()
            ->fetchAll()
        ;
    }
}
