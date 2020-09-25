<?php

declare(strict_types=1);

namespace Pest\Symfony;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
trait Example
{
    /**
     * Example description.
     */
    public function example(string $name): TestCase
    {
        $this->assertNotEmpty($name);

        return $this;
    }
}
