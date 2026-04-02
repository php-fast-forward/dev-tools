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

namespace FastForward\DevTools\Tests\PhpUnit\Runner\Extension;

use FastForward\DevTools\PhpUnit\Event\EventTracer;
use FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber;
use FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(DevToolsExtension::class)]
#[UsesClass(JoliNotifExecutionFinishedSubscriber::class)]
final class DevToolsExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function itCanBeConstructedWithCustomTracerAndSubscriber(): void
    {
        $tracer = $this->prophesize(EventTracer::class)->reveal();
        $subscriber = $this->prophesize(StartedSubscriber::class)->reveal();
        $extension = new DevToolsExtension($tracer, $subscriber);
        self::assertInstanceOf(DevToolsExtension::class, $extension);
    }

    /**
     * @return void
     */
    #[Test]
    public function itCanBeConstructedWithDefaults(): void
    {
        $extension = new DevToolsExtension();
        self::assertInstanceOf(DevToolsExtension::class, $extension);
    }
}
