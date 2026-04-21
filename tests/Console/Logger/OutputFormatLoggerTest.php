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

namespace FastForward\DevTools\Tests\Console\Logger;

use stdClass;
use DateTimeImmutable;
use FastForward\DevTools\Console\Logger\OutputFormatLogger;
use FastForward\DevTools\Console\Logger\Processor\CommandInputProcessor;
use FastForward\DevTools\Console\Logger\Processor\CommandOutputProcessor;
use FastForward\DevTools\Console\Logger\Processor\CompositeContextProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use Stringable;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(OutputFormatLogger::class)]
#[UsesClass(CommandInputProcessor::class)]
#[UsesClass(CommandOutputProcessor::class)]
#[UsesClass(CompositeContextProcessor::class)]
final class OutputFormatLoggerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $output;

    private ObjectProphecy $errorOutput;

    private ObjectProphecy $clock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->output = $this->prophesize(ConsoleOutputInterface::class);
        $this->errorOutput = $this->prophesize(OutputInterface::class);
        $this->clock = $this->prophesize(ClockInterface::class);

        $this->output->getErrorOutput()
            ->willReturn($this->errorOutput->reveal());
        $this->clock->now()
            ->willReturn(new DateTimeImmutable('2026-04-21T16:00:00+00:00'));
    }

    /**
     * @return void
     */
    #[Test]
    public function logWillWriteInterpolatedInfoMessagesToStandardOutput(): void
    {
        $logger = new OutputFormatLogger(
            new ArgvInput(['dev-tools']),
            $this->output->reveal(),
            $this->clock->reveal(),
            new CompositeContextProcessor([new CommandInputProcessor(), new CommandOutputProcessor()]),
        );

        $this->output->writeln(
            '<info>2026-04-21T16:00:00+00:00 [INFO] Hello Felipe from 2026-04-21T12:00:00+00:00 as developer</info>'
        )->shouldBeCalledOnce();
        $this->errorOutput->writeln(Argument::type('string'))
            ->shouldNotBeCalled();

        $logger->info('Hello {name} from {date} as {role}', [
            'name' => 'Felipe',
            'date' => new DateTimeImmutable('2026-04-21T12:00:00+00:00'),
            'role' => new readonly class implements Stringable {
                /**
                 * @return string
                 */
                public function __toString(): string
                {
                    return 'developer';
                }
            },
        ]);
    }

    /**
     * @return void
     */
    #[Test]
    public function logWillWriteErrorMessagesToErrorOutput(): void
    {
        $logger = new OutputFormatLogger(
            new ArgvInput(['dev-tools']),
            $this->output->reveal(),
            $this->clock->reveal(),
            new CompositeContextProcessor([new CommandInputProcessor(), new CommandOutputProcessor()]),
        );

        $this->output->writeln(Argument::type('string'))
            ->shouldNotBeCalled();
        $this->errorOutput->writeln(
            '<error>2026-04-21T16:00:00+00:00 [ERROR] Failure in [{"package":"fast-forward/dev-tools"}] for [object stdClass]</error>'
        )->shouldBeCalledOnce();

        $logger->error('Failure in {items} for {object}', [
            'items' => [
                'package' => 'fast-forward/dev-tools',
            ],
            'object' => new stdClass(),
        ]);
    }

    /**
     * @return void
     */
    #[Test]
    public function logWillWriteStructuredJsonWhenJsonOutputIsRequested(): void
    {
        $logger = new OutputFormatLogger(
            new ArgvInput(['dev-tools', '--output-format=json']),
            $this->output->reveal(),
            $this->clock->reveal(),
            new CompositeContextProcessor([new CommandInputProcessor(), new CommandOutputProcessor()]),
        );

        $this->output->writeln(
            '{"message":"Build {status}","level":"info","context":{"status":"ready","attempt":1},"timestamp":"2026-04-21T16:00:00+00:00"}'
        )->shouldBeCalledOnce();
        $this->errorOutput->writeln(Argument::type('string'))
            ->shouldNotBeCalled();

        $logger->info('Build {status}', [
            'status' => 'ready',
            'attempt' => 1,
        ]);
    }
}
