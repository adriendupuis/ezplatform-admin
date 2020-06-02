<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentUsageController extends Controller
{
    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var SearchService */
    private $searchService;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ContentTypeService $contentTypeService, SearchService $searchService, TranslatorInterface $translator)
    {
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->translator = $translator;
    }

    public function mainAction(): Response
    {
        return $this->render('@ezdesign/content_usage/main.html.twig');
    }

    public function exampleFinderTableAction(Request $request): Response
    {
        $contentTypeIdentifier = $request->get('content_type');
        try {
            if (is_numeric($contentTypeIdentifier)) {
                $contentType = $this->contentTypeService->loadContentType($contentTypeIdentifier);
            } else {
                $contentType = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
            }
        } catch (NotFoundException $notFoundException) {
            return '<strong>Content type not found.</strong>';
        }

        /** @var FieldDefinition $fieldDefinition */
        foreach ($contentType->fieldDefinitions as $fieldDefinition) {
            $fieldTypeLabels[$fieldDefinition->fieldTypeIdentifier] = $this->getFieldTypeLabel($fieldDefinition->fieldTypeIdentifier);
        }

        return $this->render('@ezdesign/content_usage/example_finder_table.html.twig', [
            'content_type' => $contentType ?? [],
            'field_type_labels' => $fieldTypeLabels ?? [],
        ]);
    }

    private function getFieldTypeLabel($fieldTypeIdentifier)
    {
        return $this->translator->trans(/* @Ignore */$fieldTypeIdentifier.'.name', [], 'fieldtypes');
    }

    public function exampleFinderSearchAction(Request $request): JsonResponse
    {
        $contentTypeIdentifier = $request->get('content_type');
        if (is_numeric($contentTypeIdentifier)) {
            $criterion = new Query\Criterion\ContentTypeId((int) $contentTypeIdentifier);
        } else {
            $criterion = new Query\Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        }
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 25);
        $searchResult = $this->searchService->findContent(Query([
            'filter' => $criterion,
            'offset' => $offset,
            'limit' => $limit,
        ]));

        foreach ($searchResult->searchHits as $searchHit) {
            //TODO
        }

        return new JsonResponse([
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }
}
