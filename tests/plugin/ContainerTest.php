<?php

namespace Pest\Symfony\Tests\Plugin;

use function Pest\Symfony\app;
use function Pest\Symfony\container;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use Symfony\Component\DependencyInjection\ContainerInterface;

it('instanciates the container', function () {
    assertInstanceOf(ContainerInterface::class, container());
});

it('returns a shared instance of the container by default', function () {
    $containerA = container();
    $containerB = container();
    assertSame($containerA, $containerB);
});

it('returns a new instance of the container when required', function () {
    $containerA = container();
    $containerB = container(app(true));
    assertNotSame($containerA, $containerB);
});
