<?php

declare(strict_types=1);

namespace Pest\Symfony;

// use Pest\Contracts\Plugins\AddsOutput;
// use Pest\Contracts\Plugins\HandlesArguments;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 */
final class Plugin
{
    private const DEFAULT_KERNEL_CLASS = 'App\\Test';

    /**
     * @var string
     */
    private static $KERNEL_CLASS;

    public static function setKernelClass(string $kernelClass): void
    {
        if (!\is_a($kernelClass, Kernel::class, true)) {
            $error = \sprintf('Expected instance of %s, %s given.', Kernel::class, $kernelClass);
            throw new InvalidArgumentException($error);
        }
        self::$KERNEL_CLASS = $kernelClass;
    }

    public static function getKernelClass(): string
    {
        if (null === self::$KERNEL_CLASS) {
            self::setKernelClass($_SERVER['KERNEL_CLASS'] ?? self::DEFAULT_KERNEL_CLASS);
        }

        return self::$KERNEL_CLASS;
    }
}
