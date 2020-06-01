<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Psr\Container\ContainerInterface;

class ContentUsageController extends Controller
{
    public function mainAction()
    {
        return $this->render('@ezdesign/content_usage/main.html.twig');
    }
}
