<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Controller;

use AdrienDupuis\EzPlatformAdminBundle\Service\ContainerNavigatorService;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ContainerNavigatorController extends Controller
{
    /** @var ContainerNavigatorService */
    private $containerNavigatorService;

    public function __construct(ContainerNavigatorService $containerNavigatorService)
    {
        $this->containerNavigatorService = $containerNavigatorService;
    }

    public function mainAction(): Response
    {
        return $this->render('@ezdesign/container_navigator/main.html.twig');
    }

    public function detailAction(string $name, string $type = 'auto'): Response
    {
        switch ($type) {
            case 'service':
                $data = $this->getService($name);
                break;
            case 'tag':
                $data = $this->getTag($name);
                break;
            case 'event':
                $data = $this->getEvent($name);
                break;
            case 'auto':
                $data = $this->getAuto($name);
                $type = $data['type'] ?? null;
                break;
            default:
                throw new InvalidArgumentException('type', 'unknown type: '.$type);
        }

        if (!empty($data) && !empty($type)) {
            return $this->render("@ezdesign/container_navigator/detail/{$type}.html.twig", $data);
        } else {
            $response = $this->render('@ezdesign/container_navigator/detail/unknown.html.twig', ['name' => $name, 'type' => $type]);
            $response->setStatusCode('404');

            return $response;
            //$this->createNotFoundException((empty($type) ? 'No service, tag nor event' : "No $type") . " found with name '$name'.");
        }
    }

    private function getService($name): ?array
    {
        return $this->containerNavigatorService->getService($name);
    }

    private function getTag($name): ?array
    {
        return $this->containerNavigatorService->getTag($name);
    }

    private function getEvent($name): ?array
    {
        return $this->containerNavigatorService->getEvent($name);
    }

    private function getAuto($name): ?array
    {
        return $this->containerNavigatorService->getAuto($name);
    }
}
