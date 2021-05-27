<?php

namespace AdrienDupuis\EzPlatformAdminBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class AdrienDupuisEzPlatformAdminExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/../Resources/config/ez_field_templates.yaml'));
        $container->prependExtensionConfig('ezpublish', $config);
    }
}
