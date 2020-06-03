<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentUsageController extends Controller
{
    /**
     * Even if optional, those field type can't be empty.
     *
     * @var string[]
     */
    public $neverEmptyFieldTypeIdentifierList = [
        'ezauthor',
        'ezboolean',
        //TODO: Check all build-in field types
    ];

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var FieldTypeService */
    private $fieldTypeService;

    /** @var SearchService */
    private $searchService;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ContentTypeService $contentTypeService, FieldTypeService $fieldTypeService, SearchService $searchService, TranslatorInterface $translator)
    {
        $this->contentTypeService = $contentTypeService;
        $this->fieldTypeService = $fieldTypeService;
        $this->searchService = $searchService;
        $this->translator = $translator;
    }

    public function mainAction(): Response
    {
        return $this->render('@ezdesign/content_usage/main.html.twig');
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getContentType(Request $request): ContentType
    {
        $contentTypeIdentifier = $request->get('content_type');
        try {
            if (is_numeric($contentTypeIdentifier)) {
                return $this->contentTypeService->loadContentType($contentTypeIdentifier);
            } else {
                return $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
            }
        } catch (NotFoundException $notFoundException) {
            throw $this->createNotFoundException('Content type not found.');
        }
    }

    public function exampleFinderTableAction(Request $request): Response
    {
        $contentType = $this->getContentType($request);

        /** @var FieldDefinition $fieldDefinition */
        foreach ($contentType->fieldDefinitions as $fieldDefinition) {
            $fieldTypeLabels[$fieldDefinition->fieldTypeIdentifier] = $this->getFieldTypeLabel($fieldDefinition->fieldTypeIdentifier);
        }

        return $this->render('@ezdesign/content_usage/example_finder_table.html.twig', [
            'content_type' => $contentType,
            'field_type_labels' => $fieldTypeLabels ?? [],
            'never_empty_field_types' => $this->neverEmptyFieldTypeIdentifierList,
        ]);
    }

    private function getFieldTypeLabel($fieldTypeIdentifier)
    {
        return $this->translator->trans(/* @Ignore */$fieldTypeIdentifier.'.name', [], 'fieldtypes');
    }

    public function exampleFinderSearchAction(Request $request): JsonResponse
    {
        $contentType = $this->getContentType($request);

        $searchResult = $this->searchService->findContent(new Query([
            'filter' => new Query\Criterion\ContentTypeId($contentType->id),
            'offset' => (int) $request->get('offset', 0),
            'limit' => (int) $request->get('limit', 25),
        ]));

        $examples = [];

        foreach ($searchResult->searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $bestExampleScore = 0;
            $goodExampleFieldDefIdentifierList = [];
            $worstExampleScore = 0;
            $badExampleFieldDefIdentifierList = [];

            foreach ($content->getFields() as $field) {
                if (in_array($field->fieldTypeIdentifier, $this->neverEmptyFieldTypeIdentifierList)) {
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
                'url' => $this->generateUrl('_ez_content_view', [ 'contentId' => $content->id ]),
                //'url_alias' => $this->generateUrl('ez_urlalias', [ 'contentId' => $content->id ]),
            ];

            if ($worstExampleScore) {
                // Bad example
                foreach ($badExampleFieldDefIdentifierList as $fieldDefIdentifier) {
                    if (!array_key_exists($fieldDefIdentifier, $examples)) {
                        $examples[$fieldDefIdentifier] = ['bads' => []];
                    }
                    $examples[$fieldDefIdentifier]['bads'][] = $exampleData;
                }
            } /* a bad example can't be a good one */ elseif ($bestExampleScore) {
                // Good example
                foreach ($goodExampleFieldDefIdentifierList as $fieldDefIdentifier) {
                    if (!array_key_exists($fieldDefIdentifier, $examples)) {
                        $examples[$fieldDefIdentifier] = [];
                    }
                    if (!array_key_exists('good', $examples[$fieldDefIdentifier]) || $bestExampleScore > $examples[$fieldDefIdentifier]['good']['score']) {
                        $examples[$fieldDefIdentifier]['good'] = $exampleData;
                    }
                }
            }
        }

        return new JsonResponse([
            'totalCount' => $searchResult->totalCount,
            'examples' => $examples,
        ]);
    }
}
