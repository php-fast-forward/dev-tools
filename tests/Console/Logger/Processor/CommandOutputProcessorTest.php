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

use FastForward\DevTools\Console\Logger\Processor\CommandOutputProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

#[CoversClass(CommandOutputProcessor::class)]
final class CommandOutputProcessorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function processWillReplaceBufferedOutputWithItsContents(): void
    {
        $processor = new CommandOutputProcessor();
        $output = new BufferedOutput();
        $output->writeln('done');

        $context = $processor->process([
            'output' => $output,
        ]);

        self::assertSame("done\n", $context['output']);
    }

    /**
     * @return void
     */
    #[Test]
    public function processWillExtractBufferedErrorOutputFromConsoleOutput(): void
    {
        $processor = new CommandOutputProcessor();
        $errorOutput = new BufferedOutput();
        $errorOutput->writeln('boom');

        $consoleOutput = $this->prophesize(ConsoleOutputInterface::class);
        $consoleOutput->getErrorOutput()
            ->willReturn($errorOutput);

        $context = $processor->process([
            'stream' => $consoleOutput->reveal(),
        ]);

        self::assertSame("boom\n", $context['error_output']);
        self::assertArrayNotHasKey('stream', $context);
    }
}
