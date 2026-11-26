<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\PhpUnit\Coverage;

use FastForward\DevTools\PhpUnit\Coverage\MinimumCoverageResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Minimum coverage parsing MUST preserve the existing numeric percentage contract.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(MinimumCoverageResolver::class)]
final class MinimumCoverageResolverTest extends TestCase
{
    /**
     * @param string|null $value the configured threshold
     * @param float|null $expected the expected resolved percentage
     *
     * @return void
     */
    #[Test]
    #[DataProvider('acceptedValues')]
    public function invokeWillResolveOptionalPercentage(?string $value, ?float $expected): void
    {
        self::assertSame($expected, (new MinimumCoverageResolver())($value));
    }

    /**
     * @param string $value the invalid configured threshold
     * @param string $message the expected validation error
     *
     * @return void
     */
    #[Test]
    #[DataProvider('rejectedValues')]
    public function invokeWillRejectInvalidPercentage(string $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new MinimumCoverageResolver())($value);
    }

    /**
     * @return iterable<string, array{?string, ?float}>
     */
    public static function acceptedValues(): iterable
    {
        yield 'absent' => [null, null];
        yield 'empty' => ['', null];
        yield 'whitespace' => [" \n\t ", null];
        yield 'lower boundary' => ['0', 0.0];
        yield 'upper boundary' => ['100', 100.0];
        yield 'trimmed decimal' => [' 80.5 ', 80.5];
        yield 'leading decimal point' => ['.5', 0.5];
        yield 'positive sign' => ['+75', 75.0];
        yield 'exponent upper boundary' => ['1e2', 100.0];
        yield 'negative exponent' => ['5e-1', 0.5];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedValues(): iterable
    {
        $numericMessage = 'The --min-coverage option MUST be a numeric percentage.';
        $rangeMessage = 'The --min-coverage option MUST be between 0 and 100.';

        yield 'not numeric' => ['invalid', $numericMessage];
        yield 'percentage suffix' => ['80%', $numericMessage];
        yield 'negative percentage' => ['-0.1', $rangeMessage];
        yield 'above upper boundary' => ['100.1', $rangeMessage];
        yield 'exponent outside range' => ['1e3', $rangeMessage];
    }
}
