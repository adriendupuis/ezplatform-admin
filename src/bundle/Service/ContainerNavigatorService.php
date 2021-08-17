<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use App\Kernel;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContainerNavigatorService
{
    private $container;
    private $eventDispatcher;
    private $classMap;
    private $eventMap;

    public function __construct(Kernel $kernel, EventDispatcherInterface $eventDispatcher)
    {
        if (!$kernel->isDebug() || !(new ConfigCache($kernel->getContainer()->getParameter('debug.container.dump'), true))->isFresh()) {
            $buildContainer = \Closure::bind(function () { return $this->buildContainer(); }, $kernel, \get_class($kernel));
            $container = $buildContainer();
            $container->getCompilerPassConfig()->setRemovingPasses([]);
            $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
            $container->compile();
        } else {
            (new XmlFileLoader($container = new ContainerBuilder(), new FileLocator()))->load($kernel->getContainer()->getParameter('debug.container.dump'));
            $locatorPass = new ServiceLocatorTagPass();
            $locatorPass->process($container);
        }

        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
        $this->classMap = require __DIR__.'/../../../../../composer'.'/autoload_classmap.php';
        $this->eventMap = [];
    }

    public function getService($serviceId)
    {
        $serviceDef = null;

        if ($this->container->hasDefinition($serviceId)) {
            $serviceDef = $this->container->getDefinition($serviceId);
        } elseif ($this->container->hasAlias($serviceId)) {
            $serviceDef = $this->container->getDefinition((string) $this->container->getAlias($serviceId)); // Alias::__toString() == id
        } elseif ($this->container->has($serviceId)) {
            $serviceDef = $this->container->get($serviceId);
        }

        if ($serviceDef instanceof Definition) {
            //dump($serviceDef->getDecoratedService());
            return [
                'type' => 'service',
                'name' => $serviceId,
                'class' => $serviceDef->getClass(),
                'aliases' => $this->getServiceAliases($serviceDef->getClass()),
                'arguments' => array_map(function ($a) {return (string) $a; }, $serviceDef->getArguments()),
                'tags' => array_keys($serviceDef->getTags()),
                'events' => [
                    'dispatched' => $this->getServiceDispatchedEvents($serviceDef->getClass()),
                    'subscribed' => $this->getServiceSubscribedEvents($serviceId),
                ],
            ];
        }

        try {
            return [
                'type' => 'service',
                'class' => get_class($serviceDef),
                'aliases' => get_class($serviceDef) === $serviceId ? [] : [$serviceId],
            ];
        } catch (\Throwable $throwable) {
        }

        return $serviceDef;
    }

    public function getServiceAliases($serviceId)
    {
        $aliases = [];

        foreach ($this->container->getAliases() as $alias => $aliasDef) {
            if ($serviceId == (string) $aliasDef) {
                $aliases[] = $alias;
            }
        }

        return array_unique($aliases);
    }

    public function getServiceDispatchedEvents($class)
    {
        if ($file = $this->getClassFile($class)) {
            $events = [];
            foreach (explode(PHP_EOL, shell_exec("grep '>dispatch' $file")) as $line) {
                preg_match('/->dispatch\([^,]+, (?<event>[^,]+)\)/', $line, $matches);
                if (!array_key_exists('event', $matches)) {
                    preg_match('/->dispatch\(new [^,]+\([^\(\)]+\), (?<event>[^,]+)\)/', $line, $matches);
                }
                if (array_key_exists('event', $matches)) {
                    $event = $matches['event'];
                    if (false !== strpos($event, '::')) {
                        $event = $this->getEventNameFromCode($event, $file);
                    } else {
                        $event = trim($event, "'");
                    }
                    $events[] = $event;
                }
            }

            return $events;
        }

        return null;
    }

    public function getServiceSubscribedEvents($serviceId)
    {
        $service = $this->container->get($serviceId);
        $events = [];
        if ($service instanceof EventSubscriberInterface) {
            $events = array_merge($events, array_keys($service->getSubscribedEvents()));
        }
        //TODO: listener

        return $events;
    }

    /**
     * @param string      $code Piece of code to identify the event (For Exemple `KernelEvents::REQUEST`)
     * @param string|null $file File where the piece of code have been found
     */
    public function getEventNameFromCode(string $code, string $file = null): ?string
    {
        if (array_key_exists($code, $this->eventMap)) {
            return $this->eventMap[$code];
        }
        if (in_array($code, $this->eventMap)) {
            return $code;
        }

        try {
            eval("\$event = $code;");

            return $this->eventMap[$code] = $event;
        } catch (\Throwable $throwable) {
        }

        [$class, $const] = explode('::', $code);

        if (!empty($file) && is_file($file)) {
            $fqcnList = [];

            $uses = array_filter(explode(PHP_EOL, trim(shell_exec("grep '^use .*".str_replace('\\', '\\\\', $class.";$' $file")))), function ($u) {return !empty($u); });
            foreach ($uses as $use) {
                preg_match('/^use (?<fqcn>.+);$/', $use, $matches);
                if (array_key_exists('fqcn', $matches)) {
                    $fqcnList[] = $matches['fqcn'];
                }
            }

            preg_match('/^namespace (?<namespace>.+);$/', trim(shell_exec("grep '^namespace .*;$' $file;")), $matches);
            $fqcnList[] = "${matches['namespace']}\\${class}";

            foreach ($fqcnList as $fqcn) {
                $event = null;
                try {
                    eval("\$event = $fqcn::$const;");

                    return $this->eventMap[$const] = $event;
                } catch (\Throwable $throwable) {
                }
            }
        }

        return null;
    }

    public function getTag($name)
    {
        $services = $this->container->findTaggedServiceIds($name);
        if (empty($services)) {
            return null;
        }

        return [
            'type' => 'tag',
            'name' => $name,
            'tag' => $name,
            'services' => $this->container->findTaggedServiceIds($name),
        ];
    }

    public function getEvent($name)
    {
        $listeners = $this->eventDispatcher->getListeners($name);
        if (count($listeners)) {
            return [
                'type' => 'event',
                'name' => $name,
                'event' => $name,
                'listeners' => array_map(function ($v) {
                    return [get_class($v[0]), $v[1]];
                }, $listeners),
            ];
        }

        return null;
    }

    public function getAuto($name): ?array
    {
        $data = array_values(array_filter([
            $this->getService($name),
            $this->getTag($name),
            $this->getEvent($name),
        ], function ($v) {return !empty($v); }));
        if (1 === count($data)) {
            return $data[0];
        } elseif (count($data)) {
            return [
                'type' => 'ambiguous',
                'name' => $name,
                'possibilities' => $data,
            ];
        }

        return null;
    }

    public function getClassFile($class): ?string
    {
        return $this->classMap[$class] ?? null;
    }

    public function getKeywords()
    {
        //TODO
        return array_unique(array_merge(
            array_keys($this->container->getDefinitions()),
            array_keys($this->container->getAliases())//,
            //array_keys($this->eventDispatcher->getListeners())
        ));
    }
}
