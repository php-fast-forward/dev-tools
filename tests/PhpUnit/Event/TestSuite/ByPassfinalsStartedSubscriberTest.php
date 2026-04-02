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

use FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;

#[CoversClass(ByPassfinalsStartedSubscriber::class)]
final class ByPassfinalsStartedSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function notifyWillEnableBypassFinals(): void
    {
        $subscriber = new ByPassfinalsStartedSubscriber();
        $event = (new ReflectionClass(Started::class))->newInstanceWithoutConstructor();

        $this->expectNotToPerformAssertions();
        $subscriber->notify($event);
    }
}
