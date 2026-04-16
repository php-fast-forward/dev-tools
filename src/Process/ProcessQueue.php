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

namespace FastForward\DevTools\Process;

use RuntimeException;
use Closure;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

/**
 * Executes queued processes sequentially while supporting detached entries and
 * optional failure suppression.
 *
 * Regular processes are executed in the order they were added and block the
 * queue until completion. Detached processes are started in the order they were
 * added but do not block subsequent entries.
 *
 * A detached process that starts successfully is considered dispatched. Because
 * this implementation does not wait for detached processes to finish during `run()`,
 * their eventual runtime exit status cannot be incorporated into the final queue
 * result. However, a detached process that cannot be started at all is treated
 * as a startup failure and MAY affect the final status code unless its failure
 * is explicitly configured to be ignored.
 *
 * To ensure detached processes finish gracefully without being killed when the
 * main PHP script ends, the queue automatically registers a shutdown handler
 * during instantiation that implicitly awaits all detached processes. They can
 * also be awaited explicitly via `wait()`.
 */
final class ProcessQueue implements ProcessQueueInterface
{
    /**
     * Stores queued process entries in insertion order.
     *
     * @var list<array{process: Process, ignoreFailure: bool, detached: bool}>
     */
    private array $entries = [];

    /**
     * Stores detached processes that have already been started and whose output
     * may still need to be drained.
     *
     * @var list<Process>
     */
    private array $runningDetachedProcesses = [];

    /**
     * Initializes the queue and secures child processes from early termination.
     */
    public function __construct()
    {
        register_shutdown_function(function (): void {
            $this->wait();
        });
    }

    /**
     * Adds a process to the queue.
     *
     * @param Process $process the process instance that SHALL be added to the queue
     * @param bool $ignoreFailure indicates whether a failure of this process MUST NOT affect the final queue result
     * @param bool $detached indicates whether this process SHALL be started without blocking the next queued process
     */
    public function add(Process $process, bool $ignoreFailure = false, bool $detached = false): void
    {
        try {
            $process->setPty(true);
        } catch (RuntimeException) {
            // PTY is not available in the current environment.
        }

        $this->entries[] = [
            'process' => $process,
            'ignoreFailure' => $ignoreFailure,
            'detached' => $detached,
        ];
    }

    /**
     * Runs the queued processes and returns the resulting status code.
     *
     * The returned status code represents the first non-zero exit code observed
     * among non-ignored blocking processes, or among non-ignored detached
     * processes that fail to start. Detached processes that start successfully
     * are not awaited iteratively inside run() and therefore do not contribute
     * their eventual runtime exit code to the returned result.
     *
     * @param OutputInterface $output the output used during execution
     *
     * @return int the final exit status code produced by the queue execution
     */
    public function run(?OutputInterface $output = new NullOutput()): int
    {
        $statusCode = self::SUCCESS;

        foreach ($this->entries as $entry) {
            $this->drainDetachedProcessesOutput($output);

            /** @var Process $process */
            $process = $entry['process'];
            $ignoreFailure = $entry['ignoreFailure'];
            $detached = $entry['detached'];

            if ($detached) {
                $startupStatusCode = $this->startDetachedProcess($process, $output);

                if (
                    ! $ignoreFailure
                    && self::SUCCESS !== $startupStatusCode
                    && self::SUCCESS === $statusCode
                ) {
                    $statusCode = $startupStatusCode;
                }

                continue;
            }

            $processStatusCode = $this->runBlockingProcess($process, $output);

            if (
                ! $ignoreFailure
                && self::SUCCESS !== $processStatusCode
                && self::SUCCESS === $statusCode
            ) {
                $statusCode = $processStatusCode;
            }
        }

        $this->drainDetachedProcessesOutput($output, true);

        return $statusCode;
    }

    /**
     * Waits for all detached processes to finish execution.
     *
     * @param ?OutputInterface $output the output interface to which process output and diagnostics MAY be written
     */
    public function wait(?OutputInterface $output = new NullOutput()): void
    {
        $output ??= new NullOutput();

        while ([] !== $this->runningDetachedProcesses) {
            $this->drainDetachedProcessesOutput($output, true);
            if ([] !== $this->runningDetachedProcesses) {
                usleep(10000);
            }
        }
    }

    /**
     * Starts a process in detached mode without waiting for completion.
     *
     * A detached process is considered successfully dispatched when its startup
     * sequence completes without throwing an exception.
     *
     * @param Process $process the process to start
     * @param OutputInterface $output the output that SHALL receive process output
     *
     * @return int returns 0 when the process starts successfully, or a non-zero
     *             value when startup fails
     */
    private function startDetachedProcess(Process $process, OutputInterface $output): int
    {
        try {
            $process->start($this->createOutputCallback($output));
            $this->runningDetachedProcesses[] = $process;

            return self::SUCCESS;
        } catch (ProcessStartFailedException) {
            return self::FAILURE;
        }
    }

    /**
     * Runs a process synchronously and returns its exit code.
     *
     * @param Process $process the process to execute
     * @param OutputInterface $output the output that SHALL receive process output
     *
     * @return int The exit code returned by the process. A startup failure is
     *             normalized to a non-zero exit code.
     */
    private function runBlockingProcess(Process $process, OutputInterface $output): int
    {
        try {
            $process->run($this->createOutputCallback($output));

            return $process->getExitCode() ?? self::FAILURE;
        } catch (ProcessStartFailedException) {
            return self::FAILURE;
        }
    }

    /**
     * Creates a callback that forwards process output to the configured output.
     *
     * The callback SHALL stream both standard output and error output exactly
     * as received, preserving ANSI escape sequences when the underlying command
     * emits them.
     *
     * @param OutputInterface $output the output destination
     *
     * @return Closure(string, string):void
     */
    private function createOutputCallback(OutputInterface $output): Closure
    {
        return static function (string $type, string $buffer) use ($output): void {
            if (
                Process::ERR === $type
                && $output instanceof ConsoleOutputInterface
            ) {
                $output->getErrorOutput()
                    ->write($buffer);

                return;
            }

            $output->write($buffer);
        };
    }

    /**
     * Drains buffered output from detached processes and removes finished ones.
     *
     * When $flushFinishedOnly is false, the implementation SHALL poll detached
     * processes and forward any output currently available. When true, it SHALL
     * perform one final drain pass and remove any finished processes from the
     * internal tracking list.
     *
     * @param OutputInterface $output the output that SHALL receive detached process output
     * @param bool $flushFinishedOnly indicates whether the method is running as the final drain pass
     */
    private function drainDetachedProcessesOutput(OutputInterface $output, bool $flushFinishedOnly = false): void
    {
        $runningProcesses = [];

        foreach ($this->runningDetachedProcesses as $process) {
            $process->getIncrementalOutput();
            $process->getIncrementalErrorOutput();

            if ($process->isRunning()) {
                $runningProcesses[] = $process;

                continue;
            }

            $remainingOutput = $process->getIncrementalOutput();
            if ('' !== $remainingOutput) {
                $output->write($remainingOutput);
            }

            $remainingErrorOutput = $process->getIncrementalErrorOutput();
            if ('' !== $remainingErrorOutput) {
                $output->write($remainingErrorOutput);
            }

            if (! $flushFinishedOnly) {
                continue;
            }
        }

        $this->runningDetachedProcesses = $runningProcesses;
    }
}
