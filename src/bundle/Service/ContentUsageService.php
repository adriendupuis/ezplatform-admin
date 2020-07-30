<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory;
use EzSystems\EzPlatformPageFieldType\Registry\LayoutDefinitionRegistry;
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

    /** @var ContentService */
    private $contentService;

    /** @var LocationService */
    private $locationService;

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var FieldTypeService */
    private $fieldTypeService;

    /** @var SearchService */
    private $searchService;

    /** @var RouterInterface */
    private $router;

    /** @var LayoutDefinitionRegistry */
    private $layoutDefinitionRegistry;

    /** @var BlockDefinitionFactory */
    private $blockDefinitionFactory;

    public function __construct(
        Connection $connection,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        FieldTypeService $fieldTypeService,
        SearchService $searchService,
        RouterInterface $router,
        LayoutDefinitionRegistry $layoutDefinitionRegistry,
        BlockDefinitionFactory $blockDefinitionFactory
    ) {
        $this->dbalConnection = $connection;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->fieldTypeService = $fieldTypeService;
        $this->searchService = $searchService;
        $this->router = $router;
        $this->layoutDefinitionRegistry = $layoutDefinitionRegistry;
        $this->blockDefinitionFactory = $blockDefinitionFactory;
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

    public function getLayoutUsage()
    {
        $layoutCountList = $this->dbalConnection->createQueryBuilder()
            ->select('p.layout, COUNT(o.id) as layout_count')
            ->from('ezpage_pages', 'p')
            ->join('p', 'ezcontentobject', 'o', 'p.content_id = o.id AND p.version_no = o.current_version')
            ->groupBy('p.layout')
            ->orderBy('layout_count', 'DESC')
            ->execute()
            ->fetchAll()
        ;

        return [
            'layout_definitions' => $this->layoutDefinitionRegistry->getLayoutDefinitions(),
            'layout_count_list' => $layoutCountList,
        ];
    }

    public function getBlockUsage()
    {
        $blockDefinitions = [];
        foreach ($this->blockDefinitionFactory->getBlockIdentifiers() as $blockIdentifier) {
            $blockDefinitions[$blockIdentifier] = $this->blockDefinitionFactory->getBlockDefinition($blockIdentifier);
        }

        $blockCountList = $this->dbalConnection->createQueryBuilder()
            ->select('b.type AS block, COUNT(o.id) AS block_count')
            ->from('ezpage_blocks', 'b')
            ->join('b', 'ezpage_map_blocks_zones', 'bz', 'b.id = bz.block_id')
            ->join('bz', 'ezpage_map_zones_pages', 'zp', 'bz.zone_id = zp.zone_id')
            ->join('zp', 'ezpage_pages', 'p', 'zp.page_id = p.id')
            ->join('p', 'ezcontentobject', 'o', 'p.content_id = o.id AND p.version_no = o.current_version')
            ->groupBy('b.type')
            ->orderBy('block_count', 'DESC')
            ->execute()
            ->fetchAll()
        ;

        return [
            'block_definitions' => $blockDefinitions,
            'block_count_list' => $blockCountList,
        ];
    }

    public function getLanguageUsage(): array
    {
        $contentCounts = [];
        foreach ($this->dbalConnection->createQueryBuilder()
            ->select(['l.locale AS language_code', 'COUNT(o.id) AS content_count'])
            ->from('ezcontentobject', 'o')
            ->leftJoin('o', 'ezcontent_language', 'l', 'o.language_mask & l.id = l.id')
            ->groupBy('l.id')
            ->orderBy('content_count', 'DESC')
            ->execute()
            ->fetchAll() as $row) {
            $contentCounts[$row['language_code']] = $row['content_count'];
        }

        return $contentCounts;
    }

    /* IDENTIFICATION */

    /** @param mixed $identifier */
    public function isId($identifier): bool
    {
        $id = (int) $identifier;

        return 0 < $id && (is_int($identifier) || $identifier === (string) $id);
    }

    /**
     * Return a content if parameter corresponds to one, null otherwise.
     *
     * @param int|string $identifier
     */
    public function findContent($identifier): ?Content
    {
        if ($this->isId($identifier)) {
            try {
                return $this->contentService->loadContent($identifier);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Return a location if parameter corresponds to one, null otherwise.
     *
     * @param int|string $identifier
     */
    public function findLocation($identifier): ?Location
    {
        if ($this->isId($identifier)) {
            try {
                return $this->locationService->loadLocation($identifier);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Return content types according to identifier (can be an ID, an identifier, an identifier with wildcards).
     *
     * @param int|string $identifier Can content the wildcard `*` (for example 'user*')
     */
    public function findContentType($identifier): array
    {
        try {
            if ($this->isId($identifier)) {
                return [$this->contentTypeService->loadContentType($identifier)];
            } elseif (is_string($identifier)) {
                if (false === strpos($identifier, '*')) {
                    return [$this->contentTypeService->loadContentTypeByIdentifier($identifier)];
                } else {
                    $identifier = str_replace('*', '%', $identifier);
                    $contentTypeIds = array_map(function ($row) {
                        return $row['id'];
                    }, $this->dbalConnection->createQueryBuilder()
                        ->select('c.id')
                        ->from('ezcontentclass', 'c')
                        ->where('c.identifier LIKE :identifier')
                        ->setParameter('identifier', $identifier)
                        ->execute()
                        ->fetchAll()
                    );
                    if (count($contentTypeIds)) {
                        return $this->contentTypeService->loadContentTypeList($contentTypeIds);
                    } else {
                        return [];
                    }
                }
            } else {
                throw new InvalidArgumentException('$identifier', 'must be an integer (id) or a string (identifier)');
            }
        } catch (NotFoundException $e) {
            return [];
        }
    }

    /**
     * Return field definitions (and their content types) according to identifier.
     *
     * @param $identifier Can content the wildcard `*` (for example 'ez*text')
     */
    public function findContentTypeField($identifier): array
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('a.contentclass_id, a.identifier')
            ->from('ezcontentclass_attribute', 'a')
            ;
        if ($this->isId($identifier)) {
            $queryBuilder->where('a.id = :identifier');
        } elseif (is_string($identifier)) {
            if (false === strpos($identifier, '*')) {
                $queryBuilder->where('a.identifier = :identifier');
                $queryBuilder->orWhere('a.data_type_string = :identifier');
            } else {
                $identifier = str_replace('*', '%', $identifier);
                $queryBuilder->where('a.identifier LIKE :identifier');
                $queryBuilder->orWhere('a.data_type_string LIKE :identifier');
            }
        } else {
            throw new InvalidArgumentException('$identifier', 'must be an integer (id) or a string (identifier)');
        }

        $contentTypeFieldRows = $queryBuilder
            ->setParameter('identifier', $identifier)
            ->execute()
            ->fetchAll()
        ;
        $contentTypes = [];
        $contentTypeFields = [];
        foreach ($contentTypeFieldRows as $contentTypeFieldRow) {
            $contentTypeId = (int) $contentTypeFieldRow['contentclass_id'];
            $contentTypeFieldIdentifier = $contentTypeFieldRow['identifier'];
            if (array_key_exists($contentTypeId, $contentTypes)) {
                $contentType = $contentTypes[$contentTypeId];
            } else {
                $contentTypes[$contentTypeId] = $contentType = $this->contentTypeService->loadContentType($contentTypeId);
            }
            $contentTypeFields["{$contentType->identifier}/$contentTypeFieldIdentifier"] = [
                'content_type' => $contentType,
                'field_definition' => $contentType->getFieldDefinition($contentTypeFieldIdentifier),
            ];
        }
        ksort($contentTypeFields);

        return $contentTypeFields;
    }
}
