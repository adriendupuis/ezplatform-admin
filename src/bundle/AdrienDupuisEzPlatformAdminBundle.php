<?php

namespace AdrienDupuis\EzPlatformAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AdrienDupuisEzPlatformAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        /** @var EzPublishCoreExtension */
        $extension = $container->getExtension('ezpublish');
        $extension->addPolicyProvider(new Security\PolicyProvider());
    }
}
