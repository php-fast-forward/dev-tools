<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Psr\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * A clock implementation that returns the current system time.
 *
 * This class implements the ClockInterface and provides a method to get the current time as a DateTimeImmutable object.
 */
final class SystemClock implements ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object.
     *
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
