<?php

declare(strict_types=1);

namespace Pest\Symfony\Tests\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader as ContainerPhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @codeCoverageIgnore
 */
class Kernel extends BaseKernel
{
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

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('./config/{packages}/*.yaml');
        $container->import('./config/{packages}/' . $this->environment . '/*.yaml');

        if (is_file(\dirname(__DIR__) . '/config/services.yaml')) {
            $container->import('./config/{services}.yaml');
            $container->import('./config/{services}_' . $this->environment . '.yaml');
        } elseif (is_file($path = \dirname(__DIR__) . '/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('./config/{routes}/' . $this->environment . '/*.yaml');
        $routes->import('./config/{routes}/*.yaml');

        if (is_file(\dirname(__DIR__) . '/config/routes.yaml')) {
            $routes->import('./config/{routes}.yaml');
        } elseif (is_file($path = \dirname(__DIR__) . '/config/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type'     => 'service',
                ],
            ]);

            $kernelClass = false !== strpos(static::class, "@anonymous\0") ? parent::class : static::class;

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', $kernelClass)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true)
                ;
            }

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');

            $container->addObjectResource($this);
            $container->fileExists($this->getProjectDir() . '/config/bundles.php');

            try {
                $configureContainer = new \ReflectionMethod($this, 'configureContainer');
            } catch (\ReflectionException $e) {
                throw new \LogicException(sprintf('"%s" uses "%s", but does not implement the required method "protected function configureContainer(ContainerConfigurator $c): void".', get_debug_type($this), MicroKernelTrait::class), 0, $e);
            }

            $configuratorClass = $configureContainer->getNumberOfParameters() > 0 && ($type = $configureContainer->getParameters()[0]->getType()) instanceof \ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            if ($configuratorClass && !is_a(ContainerConfigurator::class, $configuratorClass, true)) {
                $this->configureContainer($container, $loader);

                return;
            }

            // the user has opted into using the ContainerConfigurator
            /* @var ContainerPhpFileLoader $kernelLoader */
            $kernelLoader = $loader->getResolver()->resolve($file = $configureContainer->getFileName());
            $kernelLoader->setCurrentDir(\dirname($file));
            $instanceof = &\Closure::bind(function &() { return $this->instanceof; }, $kernelLoader, $kernelLoader)();

            $valuePreProcessor = AbstractConfigurator::$valuePreProcessor;
            AbstractConfigurator::$valuePreProcessor = function ($value) {
                return $this === $value ? new Reference('kernel') : $value;
            };

            try {
                $this->configureContainer(new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file), $loader);
            } finally {
                $instanceof = [];
                $kernelLoader->registerAliasesForSinglyImplementedInterfaces();
                AbstractConfigurator::$valuePreProcessor = $valuePreProcessor;
            }

            $container->setAlias($kernelClass, 'kernel')->setPublic(true);
        });
    }

    /**
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file);
        $kernelLoader->setCurrentDir(\dirname($file));
        $collection = new RouteCollection();

        try {
            $configureRoutes = new \ReflectionMethod($this, 'configureRoutes');
        } catch (\ReflectionException $e) {
            throw new \LogicException(sprintf('"%s" uses "%s", but does not implement the required method "protected function configureRoutes(RoutingConfigurator $routes): void".', get_debug_type($this), MicroKernelTrait::class), 0, $e);
        }

        $configuratorClass = $configureRoutes->getNumberOfParameters() > 0 && ($type = $configureRoutes->getParameters()[0]->getType()) && !$type->isBuiltin() ? $type->getName() : null;

        if ($configuratorClass && !is_a(RoutingConfigurator::class, $configuratorClass, true)) {
            trigger_deprecation('symfony/framework-bundle', '5.1', 'Using type "%s" for argument 1 of method "%s:configureRoutes()" is deprecated, use "%s" instead.', RouteCollectionBuilder::class, self::class, RoutingConfigurator::class);

            $routes = new RouteCollectionBuilder($loader);
            $this->configureRoutes($routes);

            return $routes->build();
        }

        $this->configureRoutes(new RoutingConfigurator($collection, $kernelLoader, $file, $file));

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            }
        }

        return $collection;
    }
}
