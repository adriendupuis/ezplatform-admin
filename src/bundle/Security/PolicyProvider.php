<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Security;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigBuilderInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Security\PolicyProvider\PolicyProviderInterface;

class PolicyProvider implements PolicyProviderInterface
{
    public function addPolicies(ConfigBuilderInterface $configBuilder)
    {
        $configBuilder->addConfig([
            'ad_admin' => [
                'content_usage' => null,
                'identification' => null,
            ],
        ]);
    }
}
