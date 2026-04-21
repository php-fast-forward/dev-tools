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

namespace FastForward\DevTools\Tests\Console\Command;

use Symfony\Component\Process\Process;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Command\StandardsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(StandardsCommand::class)]
#[UsesTrait(LogsCommandResults::class)]
final class StandardsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private StandardsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->processBuilder->withArgument(Argument::any())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build(Argument::any())
            ->willReturn($this->prophesize(Process::class)->reveal());

        $this->command = new StandardsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunSuiteSequentially(): void
    {
        $this->processBuilder->withArgument('--no-progress')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(4);
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())
            ->shouldBeCalledTimes(4);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(StandardsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Running code standards checks...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'Code standards checks completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && ['refactor', 'phpdoc', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenAnyCommandFails(): void
    {
        $this->processBuilder->withArgument('--no-progress')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(4);
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())
            ->shouldBeCalledTimes(4);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(StandardsCommand::FAILURE)
            ->shouldBeCalledOnce();
        $this->logger->info('Running code standards checks...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Code standards checks failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && ['refactor', 'phpdoc', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
