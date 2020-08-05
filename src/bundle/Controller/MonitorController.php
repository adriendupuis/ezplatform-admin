<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MonitorController extends Controller
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    public function mainAction(): Response
    {
        return $this->render('@ezdesign/admin/monitor.html.twig');
    }
}
