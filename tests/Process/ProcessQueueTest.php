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
use FastForward\DevTools\Console\Output\OutputCapabilityDetectorInterface;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Process\ProcessEnvironmentConfiguratorInterface;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

use function Safe\preg_replace;

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
     * @var ObjectProphecy<OutputFormatterInterface>
     */
    private ObjectProphecy $outputFormatter;

    /**
     * @var ObjectProphecy<GithubActionOutput>
     */
    private ObjectProphecy $githubActionOutput;

    /**
     * @var ObjectProphecy<ProcessEnvironmentConfiguratorInterface>
     */
    private ObjectProphecy $environmentConfigurator;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    /**
     * @var ObjectProphecy<OutputCapabilityDetectorInterface>
     */
    private ObjectProphecy $outputCapabilityDetector;

    private ProcessQueue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->output = $this->prophesize(ConsoleOutputInterface::class);
        $this->errorOutput = $this->prophesize(OutputInterface::class);
        $this->outputFormatter = $this->prophesize(OutputFormatterInterface::class);
        $this->output->getErrorOutput()
            ->willReturn($this->errorOutput->reveal());
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->getFormatter()
            ->willReturn($this->outputFormatter->reveal());
        $this->output->write("\n");
        $this->output->writeln(Argument::type('string'), Argument::type('int'));
        $this->outputFormatter->isDecorated()
            ->willReturn(true);
        $this->outputFormatter->setDecorated(Argument::type('bool'));
        $this->outputFormatter->format(Argument::type('string'))
            ->will(static fn(array $arguments): string => preg_replace('/<[^>]+>/', '', $arguments[0]));

        $this->environmentConfigurator = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);

        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->get('GITHUB_ACTIONS')
            ->willReturn(null);

        $this->outputCapabilityDetector = $this->prophesize(OutputCapabilityDetectorInterface::class);
        $this->outputCapabilityDetector->supportsAnsi(Argument::type(OutputInterface::class))
            ->willReturn(false);

        $this->githubActionOutput = $this->prophesize(GithubActionOutput::class);
        $this->githubActionOutput->group(Argument::type('string'), Argument::type(Closure::class))
            ->will(static fn(array $arguments): mixed => $arguments[1]());

        $this->queue = new ProcessQueue(
            $this->githubActionOutput->reveal(),
            $this->environmentConfigurator->reveal(),
            $this->environment->reveal(),
            $this->outputCapabilityDetector->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function addWillNotEnablePtyForQueuedProcesses(): void
    {
        $process = $this->prophesize(Process::class);
        $process->setPty(Argument::any())
            ->shouldNotBeCalled();
        $process->run(Argument::any())
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->getExitCode()
            ->willReturn(ProcessQueueInterface::SUCCESS);
        $process->isRunning()
            ->willReturn(false);

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
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
     * Creates a matcher for SymfonyStyle output blocks.
     *
     * @param string $expected the expected line fragment
     *
     * @return callable(mixed):bool the prophecy matcher callback
     */
    private function containsOutputLine(string $expected): callable
    {
        return static function (mixed $messages) use ($expected): bool {
            foreach ((array) $messages as $message) {
                if (\is_string($message) && str_contains($message, $expected)) {
                    return true;
                }
            }

            return false;
        };
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
    public function runConfiguresBlockingProcessEnvironmentBeforeExecution(): void
    {
        $process = $this->createBlockingProcessMock();
        $this->environmentConfigurator->configure($process->reveal(), $this->output->reveal())
            ->shouldBeCalledOnce();

        $this->queue->add($process->reveal());

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runWrapsBlockingProcessOutputInLocalSection(): void
    {
        $process = $this->createBlockingProcessMock();
        $this->outputCapabilityDetector->supportsAnsi($this->output->reveal())
            ->willReturn(true);
        $this->output->writeln(
            Argument::that($this->containsOutputLine('Custom nested command')),
            OutputInterface::OUTPUT_NORMAL
        )
            ->shouldBeCalled();

        $this->queue->add(process: $process->reveal(), label: 'Custom nested command');

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
        $this->environmentConfigurator->configure($detachedProcess->reveal(), $this->output->reveal())
            ->shouldBeCalledOnce();
        $this->environmentConfigurator->configure($blockingProcess->reveal(), $this->output->reveal())
            ->shouldBeCalledOnce();

        $this->queue->add(process: $detachedProcess->reveal(), detached: true);
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
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->start(Argument::type(Closure::class))
            ->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add(process: $process->reveal(), detached: true);

        self::assertSame(ProcessQueueInterface::FAILURE, $this->queue->run($this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runDetachedProcessStartFailureWithIgnoreFailureReturnsSuccess(): void
    {
        $process = $this->prophesize(Process::class);
        $process->getCommandLine()
            ->willReturn('test-command');
        $process->getWorkingDirectory()
            ->willReturn('/tmp');
        $process->isStarted()
            ->willReturn(false);
        $process->start(Argument::type(Closure::class))
            ->willThrow(new ProcessStartFailedException($process->reveal(), 'Failed'));

        $this->queue->add(process: $process->reveal(), ignoreFailure: true, detached: true);

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
        $this->outputCapabilityDetector->supportsAnsi($this->output->reveal())
            ->willReturn(true);
        $this->output->writeln(
            Argument::that($this->containsOutputLine('Running first-command')),
            OutputInterface::OUTPUT_NORMAL
        )
            ->shouldBeCalled();
        $this->output->writeln(
            Argument::that($this->containsOutputLine('Running second-command')),
            OutputInterface::OUTPUT_NORMAL
        )
            ->shouldBeCalled();

        $this->queue->add(process: $firstProcess->reveal(), detached: true, label: 'Running first-command');
        $this->queue->add(process: $secondProcess->reveal(), detached: true, label: 'Running second-command');

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
