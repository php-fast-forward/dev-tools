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

namespace FastForward\DevTools\Tests\PhpUnit\Event\TestSuite;

use FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\VarExporter\Instantiator;

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
        $event = Instantiator::instantiate(Started::class);

        $this->expectNotToPerformAssertions();
        $subscriber->notify($event);
    }
}
