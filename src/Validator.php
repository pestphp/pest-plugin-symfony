<?php

declare(strict_types=1);

namespace Pest\Symfony;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotCount;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Asserts that $subject doesn't pass validation (or does, if $expected === false).
 *
 * @param bool|int|string $expected - int to assert the number of violations,
 *                                  string to assert it contains a specific violation (format = "propertyPath: message")
 * @param mixed           $subject  - The variable to validate
 *
 * @throws \Exception
 * @phpstan-ignore-next-line
 */
function assertViolated($expected, $subject, ?string $propertyPath = null): void
{
    /** @var ValidatorInterface $validator */
    $validator  = container()->get(ValidatorInterface::class);
    $violations = \iterator_to_array($validator->validate($subject));

    // Check against a specitic property path
    if (null !== $propertyPath) {
        $violations = \array_filter(
            $violations,
            function (ConstraintViolationInterface $violation) use ($propertyPath): bool {
                return $propertyPath === $violation->getPropertyPath();
            }
        );
    }

    // Do not care about the exact number of violations
    if (\is_bool($expected)) {
        $expected ? assertNotCount(0, $violations) : assertCount(0, $violations);

        return;
    }

    // Check the exact number of violations
    if (\is_int($expected)) {
        assertCount($expected, $violations);

        return;
    }

    // Check a specific violation message is returned
    $violationMessages = \array_map(
        function (ConstraintViolationInterface $violation): string {
            return $violation->getPropertyPath() . ': ' . $violation->getMessage();
        },
        $violations
    );

    try {
        assertContains($expected, $violationMessages);
    } catch (ExpectationFailedException $e) {
        assertEquals([$expected], $violationMessages, 'Violation not found.');
    }
}
