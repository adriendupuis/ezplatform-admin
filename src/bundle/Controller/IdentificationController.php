<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use AdrienDupuis\EzPlatformAdminBundle\Form\Type\IdentificationType;
use AdrienDupuis\EzPlatformAdminBundle\Service\ContentUsageService;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class IdentificationController extends Controller
{
    /** @var ContentUsageService */
    private $contentUsageService;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        ContentUsageService $contentUsageService,
        FormFactoryInterface $formFactory
    ) {
        $this->contentUsageService = $contentUsageService;
        $this->formFactory = $formFactory;
    }

    public function identificationAction(Request $request): Response
    {
        $identifier = $request->get('id', null);

        return $this->render('@ezdesign/admin/identification.html.twig', [
            'form' => $this->formFactory
                ->createBuilder(IdentificationType::class, [
                    'id' => $identifier,
                ], [
                    'csrf_protection' => false,
                    'attr' => [
                        'name' => 'identification',
                    ],
                ])
                ->setMethod('GET')
                ->setAction($this->generateUrl('ad_admin.identification'))
                ->getForm()
                ->createView(),
            'identified_list' => empty($identifier) ? null : [
                'content' => $this->contentUsageService->findContent($identifier),
                'location' => $this->contentUsageService->findLocation($identifier),
                'content_type_list' => $this->contentUsageService->findContentType($identifier),
                'content_type_field_list' => $this->contentUsageService->findContentTypeField($identifier),
            ],
        ]);
    }
}
