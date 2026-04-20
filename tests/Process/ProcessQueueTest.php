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

use ReflectionProperty;
use Closure;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use ReflectionMethod;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessQueue::class)]
final class ProcessQueueTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private ProcessQueue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->output = $this->prophesize(OutputInterface::class);
        $this->queue = new ProcessQueue();
    }

    /**
     * @param int $exitCode
     * @param bool $isRunning
     *
     * @return ObjectProphecy
     */
    private function createProcessMock(
        ?int $exitCode = ProcessQueueInterface::SUCCESS,
        bool $isRunning = false
    ): ObjectProphecy {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn($exitCode ?? ProcessQueueInterface::FAILURE);
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->getExitCode()
            ->willReturn($exitCode);
        $process->isRunning()
            ->willReturn($isRunning);
        $process->isStarted()
            ->willReturn($isRunning);

        return $process;
    }

    /**
     * @param int $exitCode
     * @param bool $isRunning
     *
     * @return ObjectProphecy
     */
    private function createDetachedProcessMock(
        int $exitCode = ProcessQueueInterface::SUCCESS,
        bool $isRunning = false
    ): ObjectProphecy {
        $process = $this->createProcessMock($exitCode, $isRunning);
        $process->start(Argument::any())->shouldBeCalled();

        return $process;
    }

    /**
     * @return void
     */
    #[Test]
    public function addWithBlockingProcessWillAddToQueue(): void
    {
        $process = $this->createProcessMock();

        $this->queue->add($process->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function addWithDetachedProcessWillAddToQueue(): void
    {
        $process = $this->createDetachedProcessMock();

        $this->queue->add($process->reveal(), false, true);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function addWithIgnoreFailureWillAddToQueue(): void
    {
        $process = $this->createProcessMock();

        $this->queue->add($process->reveal(), true, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithEmptyQueueReturnsSuccess(): void
    {
        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithSuccessfulProcessReturnsSuccess(): void
    {
        $process = $this->createProcessMock();

        $this->queue->add($process->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithFailingProcessReturnsFailure(): void
    {
        $process = $this->createProcessMock(ProcessQueueInterface::FAILURE);

        $this->queue->add($process->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithFailingProcessAndIgnoreFailureReturnsSuccess(): void
    {
        $process = $this->createProcessMock(ProcessQueueInterface::FAILURE);

        $this->queue->add($process->reveal(), true, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartsWithoutBlocking(): void
    {
        $detachedProcess = $this->createDetachedProcessMock();
        $detachedProcess->isRunning()
            ->willReturn(true, false);
        $blockingProcess = $this->createProcessMock();

        $this->queue->add($detachedProcess->reveal(), false, true);
        $this->queue->add($blockingProcess->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWithNullExitCodeReturnsFailure(): void
    {
        $process = $this->createProcessMock(exitCode: null);

        $this->queue->add($process->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runMultipleProcessesWithOneFailureReturnsFailure(): void
    {
        $successProcess = $this->createProcessMock();
        $failingProcess = $this->createProcessMock(ProcessQueueInterface::FAILURE);

        $this->queue->add($successProcess->reveal(), false, false);
        $this->queue->add($failingProcess->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runBlockingProcessExceptionReturnsFailure(): void
    {
        $process = $this->createProcessMock(isRunning: false);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->run(Argument::any())->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureReturnsFailure(): void
    {
        $process = $this->createDetachedProcessMock(isRunning: false);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->start(Argument::any())->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal(), false, true);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureWithIgnoreFailureReturnsSuccess(): void
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->start(Argument::any())->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add($process->reveal(), true, true);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillWriteProcessOutputToOutputInterface(): void
    {
        $capturedCallback = null;

        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::that(function ($cb) use (&$capturedCallback): bool {
            $capturedCallback = $cb;

            return $cb instanceof Closure;
        }))->will(function () use (&$capturedCallback): int {
            $capturedCallback(Process::OUT, 'stdout output');
            $capturedCallback(Process::ERR, 'stderr output');

            return ProcessQueueInterface::SUCCESS;
        });
        $process->getExitCode()
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->isRunning()
            ->willReturn(false);
        $process->isStarted()
            ->willReturn(false);

        $this->output->write('stdout output')
            ->shouldBeCalled();
        $this->output->write('stderr output')
            ->shouldBeCalled();

        $this->queue->add($process->reveal(), false, false);

        $this->queue->run($this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function waitWillBlockUntilDetachedProcessesFinish(): void
    {
        $detachedProcess = $this->createDetachedProcessMock();
        $detachedProcess->isRunning()
            ->willReturn(true, false);

        $this->queue->add($detachedProcess->reveal(), false, true);
        $this->queue->run($this->output->reveal());

        // Call wait explicitly. It should retrieve the process from tracking
        // and loop exactly once before it exits because isRunning returns false.
        $this->queue->wait($this->output->reveal());

        // The assertion simply verifies the test completes and doesn't run infinitely.
        self::assertTrue(true);
    }

    /**
     * @return void
     */
    #[Test]
    public function addWillIgnorePtyFailuresAndStillQueueTheProcess(): void
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->willThrow(new RuntimeException('PTY unsupported'))
            ->shouldBeCalled();
        $process->run(Argument::any())
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->getExitCode()
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->isRunning()
            ->willReturn(false);
        $process->isStarted()
            ->willReturn(false);

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillWriteErrorOutputToConsoleErrorStream(): void
    {
        $capturedCallback = null;
        $consoleOutput = $this->prophesize(ConsoleOutputInterface::class);
        $errorOutput = $this->prophesize(OutputInterface::class);
        $process = $this->prophesize(Process::class);

        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::that(function ($callback) use (&$capturedCallback): bool {
            $capturedCallback = $callback;

            return $callback instanceof Closure;
        }))->will(function () use (&$capturedCallback): int {
            $capturedCallback(Process::OUT, 'stdout output');
            $capturedCallback(Process::ERR, 'stderr output');

            return ProcessQueueInterface::SUCCESS;
        });
        $process->getExitCode()
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->isRunning()
            ->willReturn(false);
        $process->isStarted()
            ->willReturn(false);

        $consoleOutput->write('stdout output')
            ->shouldBeCalledOnce();
        $consoleOutput->getErrorOutput()
            ->willReturn($errorOutput->reveal())
            ->shouldBeCalledOnce();
        $errorOutput->write('stderr output')
            ->shouldBeCalledOnce();

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($consoleOutput->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillFlushRemainingDetachedOutputWhenProcessFinishes(): void
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->start(Argument::any())
            ->shouldBeCalledOnce();
        $process->getIncrementalOutput()
            ->willReturn('', 'remaining stdout');
        $process->getIncrementalErrorOutput()
            ->willReturn('', 'remaining stderr');
        $process->isRunning()
            ->willReturn(false);
        $process->isStarted()
            ->willReturn(true);

        $this->output->write('remaining stdout')
            ->shouldBeCalledOnce();
        $this->output->write('remaining stderr')
            ->shouldBeCalledOnce();

        $this->queue->add($process->reveal(), false, true);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function waitWillAcceptNullOutput(): void
    {
        $detachedProcess = $this->createDetachedProcessMock();
        $detachedProcess->isRunning()
            ->willReturn(true, false);

        $this->queue->add($detachedProcess->reveal(), false, true);
        $this->queue->run($this->output->reveal());
        $this->queue->wait(null);

        self::assertTrue(true);
    }

    /**
     * @return void
     */
    #[Test]
    public function privateHelpersWillHandleStartupFailuresAndStreamBuffers(): void
    {
        $startDetachedProcess = new ReflectionMethod($this->queue, 'startDetachedProcess');
        $runBlockingProcess = new ReflectionMethod($this->queue, 'runBlockingProcess');
        $drainDetachedProcessesOutput = new ReflectionMethod($this->queue, 'drainDetachedProcessesOutput');

        $failingDetachedProcess = $this->prophesize(Process::class);
        $failingDetachedProcess->getCommandLine()
            ->willReturn('php artisan');
        $failingDetachedProcess->getWorkingDirectory()
            ->willReturn('/tmp');
        $failingDetachedProcess->isStarted()
            ->willReturn(false);
        $failingDetachedProcess->start(Argument::any())
            ->willThrow(new ProcessStartFailedException($failingDetachedProcess->reveal(), 'failed'));

        self::assertSame(
            ProcessQueueInterface::FAILURE,
            $startDetachedProcess->invoke($this->queue, $failingDetachedProcess->reveal(), $this->output->reveal()),
        );

        $failingBlockingProcess = $this->prophesize(Process::class);
        $failingBlockingProcess->getCommandLine()
            ->willReturn('php artisan');
        $failingBlockingProcess->getWorkingDirectory()
            ->willReturn('/tmp');
        $failingBlockingProcess->isStarted()
            ->willReturn(false);
        $failingBlockingProcess->run(Argument::any())
            ->willThrow(new ProcessStartFailedException($failingBlockingProcess->reveal(), 'failed'));

        self::assertSame(
            ProcessQueueInterface::FAILURE,
            $runBlockingProcess->invoke($this->queue, $failingBlockingProcess->reveal(), $this->output->reveal()),
        );

        $detachedProcess = $this->prophesize(Process::class);
        $detachedProcess->getIncrementalOutput()
            ->willReturn('', 'late stdout');
        $detachedProcess->getIncrementalErrorOutput()
            ->willReturn('', 'late stderr');
        $detachedProcess->isRunning()
            ->willReturn(false);

        $runningDetachedProcesses = new ReflectionProperty($this->queue, 'runningDetachedProcesses');
        $runningDetachedProcesses->setValue($this->queue, [$detachedProcess->reveal()]);

        $this->output->write('late stdout')
            ->shouldBeCalledOnce();
        $this->output->write('late stderr')
            ->shouldBeCalledOnce();

        $drainDetachedProcessesOutput->invoke($this->queue, $this->output->reveal(), true);

        self::assertSame([], $runningDetachedProcesses->getValue($this->queue));
    }
}
