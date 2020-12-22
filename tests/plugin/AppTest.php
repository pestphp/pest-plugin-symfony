<?php

namespace Pest\Symfony\Tests\Plugin;

use function Pest\Symfony\app;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use Symfony\Component\HttpKernel\KernelInterface;

it('instanciates the app', function () {
    assertInstanceOf(KernelInterface::class, app());
});

it('returns a shared instance of the app by default', function () {
    $appA = app();
    $appB = app();
    assertSame($appA, $appB);
});

it('returns a new instance of the app when required', function () {
    $appA = app();
    $appB = app(true);
    assertNotSame($appA, $appB);
});
