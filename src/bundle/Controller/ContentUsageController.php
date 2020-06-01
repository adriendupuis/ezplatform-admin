<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;

class ContentUsageController extends Controller
{
    public function mainAction()
    {
        return $this->render('@ezdesign/content_usage/main.html.twig');
    }
}
