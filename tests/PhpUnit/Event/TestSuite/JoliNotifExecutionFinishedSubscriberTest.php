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

namespace FastForward\DevTools\Tests\PhpUnit\Event\TestSuite;

use FastForward\DevTools\PhpUnit\Event\EventTracer;
use FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber;
use Joli\JoliNotif\NotifierInterface;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionMethod;

#[CoversClass(JoliNotifExecutionFinishedSubscriber::class)]
#[UsesClass(EventTracer::class)]
final class JoliNotifExecutionFinishedSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function getTitleWillReportSuccessWhenNoIssuesWereRecorded(): void
    {
        $subscriber = $this->createSubscriberWithCounts([
            Prepared::class => 3,
            Failed::class => 0,
            Errored::class => 0,
        ]);

        self::assertSame('✅ Test Suite Passed', $this->invokePrivateMethod($subscriber, 'getTitle'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getBodyWillSummarizePassedFailedAndErroredTests(): void
    {
        $subscriber = $this->createSubscriberWithCounts([
            Prepared::class => 5,
            Failed::class => 1,
            Errored::class => 2,
        ]);

        self::assertSame(
            "2 of 5 tests passed\n1 failure\n2 errors",
            $this->invokePrivateMethod($subscriber, 'getBody'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function getPassedTestsWillNeverReturnNegativeNumbers(): void
    {
        $subscriber = $this->createSubscriberWithCounts([
            Prepared::class => 1,
            Failed::class => 3,
            Errored::class => 1,
        ]);

        self::assertSame(0, $this->invokePrivateMethod($subscriber, 'getPassedTests'));
    }

    /**
     * @param array<class-string, int> $counts
     *
     * @return JoliNotifExecutionFinishedSubscriber
     */
    private function createSubscriberWithCounts(array $counts): JoliNotifExecutionFinishedSubscriber
    {
        $tracer = $this->prophesize(EventTracer::class);
        $tracer->count(Argument::type('string'))
            ->will(static fn(array $arguments): int => $counts[$arguments[0]] ?? 0);

        $notifier = $this->prophesize(NotifierInterface::class);

        return new JoliNotifExecutionFinishedSubscriber($tracer->reveal(), $notifier->reveal());
    }

    /**
     * @param object $subject
     * @param string $method
     *
     * @return mixed
     */
    private function invokePrivateMethod(object $subject, string $method): mixed
    {
        $reflectionMethod = new ReflectionMethod($subject, $method);

        return $reflectionMethod->invoke($subject);
    }
}
