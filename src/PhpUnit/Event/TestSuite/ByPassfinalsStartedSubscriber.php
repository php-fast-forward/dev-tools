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

namespace FastForward\DevTools\PhpUnit\Event\TestSuite;

use DG\BypassFinals;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

/**
 * Enables BypassFinals when the PHPUnit test suite starts.
 *
 * This subscriber MUST activate BypassFinals as soon as the test suite start
 * event is emitted so that final classes, final methods, and readonly
 * protections can be bypassed where the test environment requires that
 * behavior.
 *
 * This subscriber SHALL perform only the activation side effect associated
 * with the test suite start event.
 */
final class ByPassfinalsStartedSubscriber implements StartedSubscriber
{
    /**
     * Handles the PHPUnit test suite started event.
     *
     * This method MUST enable BypassFinals for the current test execution
     * context when the test suite starts.
     *
     * @param Started $event the emitted test suite started event
     *
     * @return void
     */
    public function notify(Started $event): void
    {
        BypassFinals::enable();
    }
}
