<?php

declare(strict_types=1);

namespace Pest\Symfony\Tests\App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @codeCoverageIgnore
 */
class Kernel extends BaseKernel
{
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/cache';
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/log';
    }

    public function registerBundles(): iterable
    {
        $contents = require __DIR__ . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type'     => 'service',
                ],
            ]);

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->setSynthetic(true)
                    ->setPublic(true)
                ;
            }

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');

            if ($this instanceof EventSubscriberInterface) {
                $kernelDefinition->addTag('kernel.event_subscriber');
            }

            $this->configureContainer($container, $loader);

            $container->addObjectResource($this);
        });
    }

    /**
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader)
    {
        $routes = new RouteCollectionBuilder($loader);
        $this->configureRoutes($routes);

        return $routes->build();
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $confDir = $this->getProjectDir() . '/config';

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, '/', 'glob');
    }
}
