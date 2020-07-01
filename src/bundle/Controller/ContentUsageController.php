<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use AdrienDupuis\EzPlatformAdminBundle\Service\ContentUsageService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
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
    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var ContentUsageServiceService */
    private $contentUsageService;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ContentTypeService $contentTypeService,
        ContentUsageService $contentUsageService,
        TranslatorInterface $translator
    ) {
        $this->contentTypeService = $contentTypeService;
        $this->contentUsageService = $contentUsageService;
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
            'never_empty_field_types' => $this->contentUsageService->neverEmptyFieldTypeIdentifierList,
        ]);
    }

    private function getFieldTypeLabel($fieldTypeIdentifier)
    {
        return $this->translator->trans(/** @Ignore */$fieldTypeIdentifier.'.name', [], 'fieldtypes');
    }

    public function exampleFinderSearchAction(Request $request): JsonResponse
    {
        return new JsonResponse($this->contentUsageService->findExamples(
            $this->getContentType($request),
            (int) $request->get('limit', 25),
            (int) $request->get('offset', 0),
            $request->get('language')
        ));
    }
}
