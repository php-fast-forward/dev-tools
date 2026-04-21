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

namespace FastForward\DevTools\Tests\Console\Logger\Processor;

use FastForward\DevTools\Console\Logger\Processor\CompositeContextProcessor;
use FastForward\DevTools\Console\Logger\Processor\ContextProcessorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(CompositeContextProcessor::class)]
final class CompositeContextProcessorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function processWillApplyProcessorsSequentially(): void
    {
        $first = $this->prophesize(ContextProcessorInterface::class);
        $second = $this->prophesize(ContextProcessorInterface::class);
        $first->process([
            'value' => 'initial',
        ])
            ->willReturn([
                'value' => 'first',
            ])
            ->shouldBeCalledOnce();
        $second->process([
            'value' => 'first',
        ])
            ->willReturn([
                'value' => 'second',
            ])
            ->shouldBeCalledOnce();

        $processor = new CompositeContextProcessor([$first->reveal(), $second->reveal()]);

        self::assertSame([
            'value' => 'second',
        ], $processor->process([
            'value' => 'initial',
        ]));
    }
}
