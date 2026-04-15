<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Process;

use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    private function createProcessMock(int $exitCode, bool $isRunning = false): ObjectProphecy
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn($exitCode);
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->getExitCode()
            ->willReturn($exitCode);
        $process->isRunning()
            ->willReturn($isRunning);

        return $process;
    }

    /**
     * @param int $exitCode
     * @param bool $isRunning
     *
     * @return ObjectProphecy
     */
    private function createDetachedProcessMock(int $exitCode, bool $isRunning = false): ObjectProphecy
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn($exitCode);
        $process->start(Argument::any())->shouldBeCalled();
        $process->getIncrementalOutput()
            ->willReturn('');
        $process->getIncrementalErrorOutput()
            ->willReturn('');
        $process->getExitCode()
            ->willReturn($exitCode);
        $process->isRunning()
            ->willReturn($isRunning);

        return $process;
    }

    /**
     * @return void
     */
    #[Test]
    public function addWithBlockingProcessWillAddToQueue(): void
    {
        $process = $this->createProcessMock(0);

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
        $process = $this->createDetachedProcessMock(0, false);

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
        $process = $this->createProcessMock(0);

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
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn(0);
        $process->getExitCode()
            ->willReturn(0);

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
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn(1);
        $process->getExitCode()
            ->willReturn(1);

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
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn(1);
        $process->getExitCode()
            ->willReturn(1);

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
        $detachedProcess = $this->prophesize(Process::class);
        $detachedProcess->setPty(true)
            ->shouldBeCalled();
        $detachedProcess->start(Argument::any())->shouldBeCalled();
        $detachedProcess->getIncrementalOutput()
            ->willReturn('');
        $detachedProcess->getIncrementalErrorOutput()
            ->willReturn('');
        $detachedProcess->isRunning()
            ->willReturn(true);

        $blockingProcess = $this->prophesize(Process::class);
        $blockingProcess->setPty(true)
            ->shouldBeCalled();
        $blockingProcess->run(Argument::any())->willReturn(0);
        $blockingProcess->getExitCode()
            ->willReturn(0);

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
        $process = $this->prophesize(Process::class);
        $process->setPty(true)
            ->shouldBeCalled();
        $process->run(Argument::any())->willReturn(1);
        $process->getExitCode()
            ->willReturn(null);

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
        $successProcess = $this->prophesize(Process::class);
        $successProcess->setPty(true)
            ->shouldBeCalled();
        $successProcess->run(Argument::any())->willReturn(0);
        $successProcess->getExitCode()
            ->willReturn(0);

        $failingProcess = $this->prophesize(Process::class);
        $failingProcess->setPty(true)
            ->shouldBeCalled();
        $failingProcess->run(Argument::any())->willReturn(1);
        $failingProcess->getExitCode()
            ->willReturn(1);

        $this->queue->add($successProcess->reveal(), false, false);
        $this->queue->add($failingProcess->reveal(), false, false);

        $result = $this->queue->run($this->output->reveal());

        self::assertSame(ProcessQueueInterface::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureReturnsFailure(): void
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
}
