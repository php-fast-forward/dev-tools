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

namespace FastForward\DevTools\Tests\Process;

use Closure;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessQueue::class)]
#[UsesClass(GithubActionOutput::class)]
final class ProcessQueueTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ConsoleOutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $errorOutput;

    /**
     * @var ObjectProphecy<GithubActionOutput>
     */
    private ObjectProphecy $githubActionOutput;

    private ProcessQueue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->output = $this->prophesize(ConsoleOutputInterface::class);
        $this->errorOutput = $this->prophesize(OutputInterface::class);
        $this->output->getErrorOutput()
            ->willReturn($this->errorOutput->reveal());

        $this->githubActionOutput = $this->prophesize(GithubActionOutput::class);
        $this->githubActionOutput->group(Argument::type('string'), Argument::type(Closure::class))
            ->will(static fn(array $arguments): mixed => $arguments[1]());

        $this->queue = new ProcessQueue($this->githubActionOutput->reveal());
    }

    /**
     * @param ObjectProphecy<Process> $process
     *
     * @return void
     */
    private function expectPtyConfiguration(ObjectProphecy $process): void
    {
        if (! Process::isPtySupported()) {
            return;
        }

        $process->setPty(true)
            ->willReturn($process->reveal())
            ->shouldBeCalled();
    }

    /**
     * @param ?int $exitCode
     * @param bool $isRunning
     *
     * @return ObjectProphecy<Process>
     */
    private function createBlockingProcessMock(
        ?int $exitCode = ProcessQueueInterface::SUCCESS,
        bool $isRunning = false,
    ): ObjectProphecy {
        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->run(Argument::any())
            ->willReturn($exitCode ?? ProcessQueueInterface::FAILURE);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->getExitCode()
            ->willReturn($exitCode);
        $process->isRunning()
            ->willReturn($isRunning);
        $process->isStarted()
            ->willReturn($isRunning);

        return $process;
    }

    /**
     * @param bool ...$runningSequence
     *
     * @return ObjectProphecy<Process>
     */
    private function createDetachedProcessMock(bool ...$runningSequence): ObjectProphecy
    {
        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->start(Argument::type(Closure::class))
            ->shouldBeCalled();
        $process->isRunning()
            ->willReturn(...$runningSequence);
        $process->isStarted()
            ->willReturn(false);
        $process->wait()
            ->shouldBeCalled();

        return $process;
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithEmptyQueueReturnsSuccess(): void
    {
        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithSuccessfulBlockingProcessReturnsSuccess(): void
    {
        $process = $this->createBlockingProcessMock();

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithFailingBlockingProcessReturnsFailure(): void
    {
        $process = $this->createBlockingProcessMock(ProcessQueueInterface::FAILURE);

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithIgnoredFailingBlockingProcessReturnsSuccess(): void
    {
        $process = $this->createBlockingProcessMock(ProcessQueueInterface::FAILURE);

        $this->queue->add($process->reveal(), true);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithNullExitCodeReturnsFailure(): void
    {
        $process = $this->createBlockingProcessMock(exitCode: null);

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runBlockingProcessExceptionReturnsFailure(): void
    {
        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->run(Argument::any())
            ->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartsWithoutBlockingAndWaitsAtTheEnd(): void
    {
        $detachedProcess = $this->createDetachedProcessMock(true, false);
        $blockingProcess = $this->createBlockingProcessMock();

        $this->queue->add($detachedProcess->reveal(), detached: true);
        $this->queue->add($blockingProcess->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureReturnsFailure(): void
    {
        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->start(Argument::type(Closure::class))
            ->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal(), detached: true);

        self::assertSame(ProcessQueueInterface::FAILURE, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureWithIgnoreFailureReturnsSuccess(): void
    {
        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->start(Argument::type(Closure::class))
            ->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal(), ignoreFailure: true, detached: true);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillWriteBlockingProcessOutputToStandardAndErrorOutputs(): void
    {
        $capturedCallback = null;

        $process = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($process);
        $process->run(Argument::that(function ($callback) use (&$capturedCallback): bool {
            $capturedCallback = $callback;

            return $callback instanceof Closure;
        }))->will(function () use (&$capturedCallback): int {
            $capturedCallback(Process::OUT, 'stdout output');
            $capturedCallback(Process::ERR, 'stderr output');

            return ProcessQueueInterface::SUCCESS;
        });
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->getExitCode()
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->isRunning()
            ->willReturn(false);

        $this->output->write('stdout output')
            ->shouldBeCalled();
        $this->errorOutput->write('stderr output')
            ->shouldBeCalled();

        $this->queue->add($process->reveal());

        $this->queue->run($this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function waitWillFlushFinishedDetachedOutputWithoutWaitingForEveryProcess(): void
    {
        $capturedFirstCallback = null;
        $capturedSecondCallback = null;

        $firstProcess = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($firstProcess);
        $firstProcess->getCommandLine()
            ->willReturn('first-command');
        $firstProcess->getWorkingDirectory()
            ->willReturn('/tmp');
        $firstProcess->start(Argument::that(function ($callback) use (&$capturedFirstCallback): bool {
            $capturedFirstCallback = $callback;

            return $callback instanceof Closure;
        }))->shouldBeCalled();
        $firstProcess->isRunning()
            ->willReturn(false);
        $firstProcess->wait()
            ->will(function () use (&$capturedFirstCallback): int {
                $capturedFirstCallback(Process::OUT, 'first output');

                return ProcessQueueInterface::SUCCESS;
            })->shouldBeCalled();

        $secondProcess = $this->prophesize(Process::class);
        $this->expectPtyConfiguration($secondProcess);
        $secondProcess->getCommandLine()
            ->willReturn('second-command');
        $secondProcess->getWorkingDirectory()
            ->willReturn('/tmp');
        $secondProcess->start(Argument::that(function ($callback) use (&$capturedSecondCallback): bool {
            $capturedSecondCallback = $callback;

            return $callback instanceof Closure;
        }))->shouldBeCalled();
        $secondProcess->isRunning()
            ->willReturn(true, false);
        $secondProcess->wait()
            ->will(function () use (&$capturedSecondCallback): int {
                $capturedSecondCallback(Process::OUT, 'second output');

                return ProcessQueueInterface::SUCCESS;
            })->shouldBeCalled();

        $this->output->write('first output')
            ->shouldBeCalled();
        $this->output->write('second output')
            ->shouldBeCalled();

        $this->queue->add($firstProcess->reveal(), detached: true);
        $this->queue->add($secondProcess->reveal(), detached: true);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->queue);
    }
}
