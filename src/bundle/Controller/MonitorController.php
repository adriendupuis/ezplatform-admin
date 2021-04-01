<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MonitorController extends Controller
{
    public function mainAction(): Response
    {
        return $this->render('@ezdesign/admin/monitor.html.twig');
    }
}
