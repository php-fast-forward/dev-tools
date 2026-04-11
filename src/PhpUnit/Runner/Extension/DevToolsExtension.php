<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\PhpUnit\Runner\Extension;

use FastForward\DevTools\PhpUnit\Event\EventTracer;
use FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber;
use FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Registers the Joli notification tracer within the PHPUnit extension lifecycle.
 *
 * This extension MUST register a tracer capable of observing PHPUnit events so
 * that desktop notifications can be dispatched when the test execution
 * finishes.
 *
 * The tracer dependency MAY be overridden to allow alternative notification
 * strategies or custom tracing behavior, but any provided implementation MUST
 * satisfy PHPUnit's tracer contract.
 */
final readonly class DevToolsExtension implements Extension
{
    private ExecutionFinishedSubscriber $executionFinishedSubscriber;

    /**
     * Creates a new extension instance.
     *
     * When no tracer is explicitly provided, the default Joli notification
     * tracer SHALL be used.
     *
     * @param Tracer $tracer the tracer instance responsible for collecting
     *                       PHPUnit events and producing notifications
     * @param StartedSubscriber $startedSubscriber
     * @param ExecutionFinishedSubscriber|null $executionFinishedSubscriber
     */
    public function __construct(
        private Tracer $tracer = new EventTracer(),
        private StartedSubscriber $startedSubscriber = new ByPassfinalsStartedSubscriber(),
        ?ExecutionFinishedSubscriber $executionFinishedSubscriber = null
    ) {
        $this->executionFinishedSubscriber = $executionFinishedSubscriber ?? new JoliNotifExecutionFinishedSubscriber(
            $tracer
        );
    }

    /**
     * Bootstraps the extension and registers its tracer with PHPUnit.
     *
     * This method MUST register the configured tracer with the provided facade
     * so that the tracer can receive PHPUnit execution events during the test
     * run.
     * The configuration and parameter collection are part of the extension
     * contract and MAY be used by future implementations, even though they are
     * not currently required by this implementation.
     *
     * @param Configuration $configuration the resolved PHPUnit runtime
     *                                     configuration
     * @param Facade $facade the PHPUnit extension facade used to register
     *                       runtime integrations
     * @param ParameterCollection $parameters the user-defined extension
     *                                        parameters passed by PHPUnit
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerTracer($this->tracer);
        $facade->registerSubscriber($this->startedSubscriber);
        $facade->registerSubscriber($this->executionFinishedSubscriber);
    }
}
