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

use Symfony\Component\Console\Output\BufferedOutput;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Command\ReportsCommand;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\DevToolsPathResolver;
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
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(ReportsCommand::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesTrait(LogsCommandResults::class)]
final class ReportsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private ReportsCommand $command;

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
        $this->process = $this->prophesize(Process::class);

        $this->input->getOption('target')
            ->willReturn(ManagedWorkspace::getOutputDirectory());
        $this->input->getOption('coverage')
            ->willReturn(ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE));
        $this->input->getOption('metrics')
            ->willReturn(ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS));
        $this->input->getOption('cache-dir')
            ->willReturn(ManagedWorkspace::getCacheDirectory());
        $this->input->hasParameterOption('--cache-dir', true)
            ->willReturn(false);
        $this->input->getOption('cache')
            ->willReturn(false);
        $this->input->getOption('no-cache')
            ->willReturn(false);
        $this->input->getOption('progress')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->getFormatter()
            ->willReturn(new OutputFormatter());
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build(Argument::any())->willReturn($this->process->reveal());

        $this->command = new ReportsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunReportsAndReturnSuccess(): void
    {
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Generating frontpage for Fast Forward documentation...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'Documentation reports generated successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCaptureBufferedOutputWhenJsonIsRequested(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(ReportsCommand::FAILURE)
            ->shouldBeCalledOnce();
        $this->logger->info('Generating frontpage for Fast Forward documentation...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Documentation reports generation failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof BufferedOutput),
        )->shouldBeCalled();

        self::assertSame(ReportsCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillForwardProgressToNestedCommandsWhenRequested(): void
    {
        $this->input->getOption('progress')
            ->willReturn(true);
        $this->processBuilder->withArgument('--progress')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(3);
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCacheWillForwardCacheOnlyToDocsAndTests(): void
    {
        $this->input->getOption('cache')
            ->willReturn(true);
        $this->input->hasParameterOption('--cache-dir', true)
            ->willReturn(true);
        $this->processBuilder->withArgument('--cache')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledTimes(2);
        $this->processBuilder->withArgument('--cache-dir', ManagedWorkspace::getCacheDirectory('docs'))
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--cache-dir', ManagedWorkspace::getCacheDirectory('tests'))
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithNoCacheWillForwardNoCacheOnlyToDocsAndTests(): void
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
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
