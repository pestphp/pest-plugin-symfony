<?php

declare(strict_types=1);

namespace Pest\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

function app(bool $reInstanciate = false): Kernel
{
    static $kernel;

    if (null === $kernel || $reInstanciate) {
        $env    = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug  = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        $kernelClass = Plugin::getKernelClass();
        $kernel      = new $kernelClass((string) $env, (bool) $debug);
        $kernel->boot();
    }

    return $kernel;
}

function container(?Kernel $app = null): ContainerInterface
{
    $app        = $app ?? app();
    $container  = $app->getContainer();

    return $container->has('test.service_container') ? $container->get('test.service_container') : $container;
}
