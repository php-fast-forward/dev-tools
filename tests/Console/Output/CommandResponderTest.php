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

namespace FastForward\DevTools\Tests\Console\Output;

use Prophecy\Argument;
use FastForward\DevTools\Console\Output\CommandResponder;
use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRendererInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\OutputFormatResolverInterface;
use FastForward\DevTools\Console\Output\ResolvedCommandResponder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(CommandResponder::class)]
#[CoversClass(CommandResult::class)]
#[CoversClass(ResolvedCommandResponder::class)]
final class CommandResponderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function fromWillCreateResolvedResponderUsingResolvedFormat(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);
        $outputFormatResolver = $this->prophesize(OutputFormatResolverInterface::class);
        $commandResultRenderer = $this->prophesize(CommandResultRendererInterface::class);

        $outputFormatResolver->resolve($input->reveal())
            ->willReturn(OutputFormat::JSON)
            ->shouldBeCalled();

        $responder = new CommandResponder($outputFormatResolver->reveal(), $commandResultRenderer->reveal());

        self::assertInstanceOf(ResolvedCommandResponder::class, $responder->from($input->reveal(), $output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function successWillRenderSuccessResultAndReturnConfiguredExitCode(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $commandResultRenderer = $this->prophesize(CommandResultRendererInterface::class);

        $commandResultRenderer->render(
            $output->reveal(),
            Argument::that(static fn(object $result): bool => 'success' === $result->status
                && 'Everything is fine.' === $result->message),
            OutputFormat::TEXT,
        )->shouldBeCalled();

        $responder = new ResolvedCommandResponder(
            $output->reveal(),
            OutputFormat::TEXT,
            $commandResultRenderer->reveal(),
        );

        self::assertSame(5, $responder->success('Everything is fine.', [], 5));
    }

    /**
     * @return void
     */
    #[Test]
    public function failureWillRenderFailureResultAndReturnConfiguredExitCode(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $commandResultRenderer = $this->prophesize(CommandResultRendererInterface::class);

        $commandResultRenderer->render(
            $output->reveal(),
            Argument::that(static fn(object $result): bool => 'failure' === $result->status
                && 'Something failed.' === $result->message),
            OutputFormat::JSON,
        )->shouldBeCalled();

        $responder = new ResolvedCommandResponder(
            $output->reveal(),
            OutputFormat::JSON,
            $commandResultRenderer->reveal(),
        );

        self::assertSame(9, $responder->failure('Something failed.', [], 9));
    }
}
