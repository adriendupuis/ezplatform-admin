<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Tab;

use AdrienDupuis\EzPlatformAdminBundle\Form\Type\ExampleFinderType;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ExampleFinder extends AbstractTab
{
    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory
    ) {
        parent::__construct($twig, $translator);
        $this->formFactory = $formFactory;
    }

    public function getIdentifier(): string
    {
        return 'ad-admin-example-finder-tab';
    }

    public function getName(): string
    {
        return /** @Desc("Example Finder Tab") */
            $this->translator->trans('example_finder', [], 'ad_admin_content_usage');
    }

    public function renderView(array $parameters): string
    {
        return $this->twig->render('@ezdesign/tab/example_finder.html.twig', [
            'form' => $this->formFactory->create(ExampleFinderType::class, null, [
                'attr' => [
                    'id' => 'example_finder_form',
                ],
                'csrf_protection' => false,
                'method' => 'GET', // AJAX in fact
            ])->createView(),
        ]);
    }
}
