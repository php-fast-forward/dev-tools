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

namespace FastForward\DevTools\PhpUnit\Event;

use PHPUnit\Event\Event;
use PHPUnit\Event\Tracer\Tracer;

/**
 * Collects PHPUnit events grouped by their concrete event class.
 *
 * This tracer MUST store every received event instance under its fully
 * qualified class name so tests MAY later inspect which events were emitted
 * and how many times each event type occurred during execution.
 *
 * The collected events SHALL remain available in memory for the lifetime of
 * this tracer instance. Consumers SHOULD treat the stored event collection as
 * test-support state and SHOULD NOT rely on it for production behavior.
 *
 * @codeCoverageIgnore
 */
class EventTracer implements Tracer
{
    /**
     * Stores traced events grouped by their concrete event class name.
     *
     * Each key MUST be a fully qualified event class name and each value MUST
     * contain the list of event instances received for that class.
     *
     * @var array<class-string<Event>, list<Event>>
     */
    private array $events = [];

    /**
     * Records a PHPUnit event in the internal event registry.
     *
     * This method MUST group the event by its concrete runtime class and SHALL
     * append the received instance to the corresponding event list without
     * discarding previously traced events of the same type.
     *
     * @param Event $event the PHPUnit event instance to record
     *
     * @return void
     */
    public function trace(Event $event): void
    {
        if (! \array_key_exists($event::class, $this->events)) {
            $this->events[$event::class] = [];
        }

        $this->events[$event::class][] = $event;
    }

    /**
     * Returns the number of traced events for the given event class.
     *
     * This method MUST return the exact number of stored events matching the
     * provided fully qualified event class name. When no events were recorded
     * for the given class, the method SHALL return 0.
     *
     * @param class-string<Event> $eventClass the fully qualified PHPUnit event
     *                                        class name to count
     *
     * @return int the number of recorded events for the specified class
     */
    public function count(string $eventClass): int
    {
        return \array_key_exists($eventClass, $this->events)
            ? \count($this->events[$eventClass])
            : 0;
    }
}
