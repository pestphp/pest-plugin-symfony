<?php

declare(strict_types=1);

namespace Pest\Symfony;

use LogicException;
use Pest\Exceptions\ShouldNotHappen;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

function app(bool $reInstanciate = false): KernelInterface
{
    static $kernel;

    if (null === $kernel || $reInstanciate) {
        // @phpstan-ignore-next-line
        $testCase = new class() extends KernelTestCase {
            public function getKernel(): KernelInterface
            {
                self::bootKernel();

                return self::$kernel;
            }
        };
        $kernel = $testCase->getKernel();
    }

    return $kernel;
}

/**
 * @phpstan-ignore-next-line
 */
function container(?KernelInterface $app = null): ContainerInterface
{
    $app       = $app ?? app();
    $container = $app->getContainer();

    if ($container->has('test.service_container')) {
        $container = $container->get('test.service_container');
    }

    if (!$container instanceof ContainerInterface) {
        throw new ShouldNotHappen(new LogicException('Unable to retrieve ContainerInterface'));
    }

    return $container;
}
