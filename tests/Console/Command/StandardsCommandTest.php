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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\DevToolsPathResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(StandardsCommand::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(DevToolsPathResolver::class)]
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
        $this->input->getOption('cache')
            ->willReturn(false);
        $this->input->getOption('no-cache')
            ->willReturn(false);
        $this->input->getOption('cache-dir')
            ->willReturn('.dev-tools/cache');
        $this->input->hasParameterOption('--cache-dir', true)
            ->willReturn(false);
        $this->input->getOption('progress')
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
                && ['refactor', 'php-cs-fixer', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenAnyCommandFails(): void
    {
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
                && ['refactor', 'php-cs-fixer', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCacheWillForwardCacheOnlyToCacheAwareNestedCommands(): void
    {
        $this->input->getOption('cache')
            ->willReturn(true);
        $this->input->hasParameterOption('--cache-dir', true)
            ->willReturn(true);
        $this->processBuilder->withArgument('--cache')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(2);
        $this->processBuilder->withArgument('--cache-dir', '.dev-tools/cache/php-cs-fixer')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--cache-dir', '.dev-tools/cache/reports')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())
            ->shouldBeCalledTimes(4);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(StandardsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithNoCacheWillForwardNoCacheOnlyToCacheAwareNestedCommands(): void
    {
        $this->input->getOption('no-cache')
            ->willReturn(true);
        $this->input->hasParameterOption('--cache-dir', true)
            ->willReturn(true);
        $this->processBuilder->withArgument('--no-cache')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(2);
        $this->processBuilder->withArgument('--cache-dir', Argument::cetera())
            ->shouldNotBeCalled();
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())
            ->shouldBeCalledTimes(4);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(StandardsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
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
