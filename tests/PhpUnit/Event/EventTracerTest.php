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

namespace FastForward\DevTools\Tests\PhpUnit\Event;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use FastForward\DevTools\PhpUnit\Event\EventTracer;
use PHPUnit\Event\Event;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventTracer::class)]
final class EventTracerTest extends TestCase
{
    private EventTracer $tracer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->tracer = new EventTracer();
    }

    /**
     * @return array
     */
    public static function eventProvider(): array
    {
        $eventClasses = [Failed::class, Errored::class, Prepared::class, Started::class, Finished::class];
        $data = [];
        foreach ($eventClasses as $class) {
            $ref = new ReflectionClass($class);
            $event = $ref->newInstanceWithoutConstructor();
            $data[] = [$event, $class];
        }

        return $data;
    }

    /**
     * @param Event $event
     * @param string $class
     *
     * @return void
     */
    #[Test]
    #[DataProvider('eventProvider')]
    public function traceStoresEventsByClass(Event $event, string $class): void
    {
        $this->tracer->trace($event);
        $this->tracer->trace($event);
        self::assertSame(2, $this->tracer->count($class));
    }

    /**
     * @param Event $event
     * @param string $class
     *
     * @return void
     */
    #[Test]
    #[DataProvider('eventProvider')]
    public function countReturnsCorrectNumberOfEvents(Event $event, string $class): void
    {
        $this->tracer->trace($event);
        $this->tracer->trace($event);
        self::assertSame(2, $this->tracer->count($class));
        self::assertSame(0, $this->tracer->count('NonExistentClass'));
    }
}
