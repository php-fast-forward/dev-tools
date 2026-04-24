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

namespace FastForward\DevTools\Process;

use Closure;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use ReflectionProperty;
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
 * Buffered detached output is flushed only after the blocking portion of the
 * queue has finished, which keeps later process output from interleaving with
 * currently running blocking commands.
 */
final class ProcessQueue implements ProcessQueueInterface
{
    private static ?ReflectionProperty $commandLineProperty = null;

    /**
     * Stores queued process entries in insertion order.
     *
     * @var list<array{process: Process, ignoreFailure: bool, detached: bool, label: string}>
     */
    private array $entries = [];

    /**
     * Stores detached processes that have already been started and whose output
     * SHALL be emitted only after blocking processes finish.
     *
     * @var list<object{process: Process, label: string, standardOutput: string, errorOutput: string}>
     */
    private array $runningDetachedProcesses = [];

    /**
     * @param GithubActionOutput $githubActionOutput wraps grouped queue output in GitHub Actions logs when supported
     */
    public function __construct(
        private readonly GithubActionOutput $githubActionOutput,
    ) {}

    /**
     * Adds a process to the queue.
     *
     * @param Process $process the process instance that SHALL be added to the queue
     * @param bool $ignoreFailure indicates whether a failure of this process MUST NOT affect the final queue result
     * @param bool $detached indicates whether this process SHALL be started without blocking the next queued process
     * @param ?string $label an optional label that MAY be used to present the process output as a grouped block
     */
    public function add(
        Process $process,
        bool $ignoreFailure = false,
        bool $detached = false,
        ?string $label = null
    ): void {
        $this->entries[] = [
            'process' => $process,
            'ignoreFailure' => $ignoreFailure,
            'detached' => $detached,
            'label' => $this->resolveLabel($process, $label),
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
            /** @var Process $process */
            $process = $entry['process'];
            $ignoreFailure = $entry['ignoreFailure'];
            $detached = $entry['detached'];
            /** @var string $label */
            $label = $entry['label'];

            if ($detached) {
                $startupStatusCode = $this->startDetachedProcess($process, $label);

                if (
                    ! $ignoreFailure
                    && self::SUCCESS !== $startupStatusCode
                    && self::SUCCESS === $statusCode
                ) {
                    $statusCode = $startupStatusCode;
                }

                continue;
            }

            $processStatusCode = $this->runLabeledBlockingProcess($process, $output, $label);

            if (
                ! $ignoreFailure
                && self::SUCCESS !== $processStatusCode
                && self::SUCCESS === $statusCode
            ) {
                $statusCode = $processStatusCode;
            }
        }

        $this->wait($output);
        $this->entries = [];

        return $statusCode;
    }

    /**
     * Waits for detached processes to finish and flushes completed buffered output.
     *
     * @param ?OutputInterface $output the output interface to which process output and diagnostics MAY be written
     */
    public function wait(?OutputInterface $output = null): void
    {
        $output ??= new NullOutput();

        while ([] !== $this->runningDetachedProcesses) {
            if ($this->flushDetachedProcessesOutput($output)) {
                continue;
            }

            usleep(10000);
        }
    }

    /**
     * Starts a process in detached mode without waiting for completion.
     *
     * A detached process is considered successfully dispatched when its startup
     * sequence completes without throwing an exception.
     *
     * @param Process $process the process to start
     * @param string $label the label used when presenting the buffered output
     *
     * @return int returns 0 when the process starts successfully, or a non-zero
     *             value when startup fails
     */
    private function startDetachedProcess(Process $process, string $label): int
    {
        $entry = (object) [
            'process' => $process,
            'label' => $label,
            'standardOutput' => '',
            'errorOutput' => '',
        ];

        try {
            $process->start($this->createBufferedOutputCallback($entry));
            $this->runningDetachedProcesses[] = $entry;

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
     * Runs a blocking process inside a grouped GitHub Actions log section.
     *
     * @param Process $process the process to execute
     * @param OutputInterface $output the output that SHALL receive process output
     * @param string $label the label that SHALL be used to group command output
     *
     * @return int the resulting process exit code
     */
    private function runLabeledBlockingProcess(Process $process, OutputInterface $output, string $label): int
    {
        $runBlockingProcess = fn(): int => $this->runBlockingProcess($process, $output);

        return $this->githubActionOutput->group($label, $runBlockingProcess);
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
     * Creates a callback that buffers detached process output until flushing.
     *
     * @param object{process: Process, label: string, standardOutput: string, errorOutput: string} $entry
     *
     * @return Closure(string, string):void
     */
    private function createBufferedOutputCallback(object $entry): Closure
    {
        return static function (string $type, string $buffer) use ($entry): void {
            if (Process::ERR === $type) {
                $entry->errorOutput .= $buffer;

                return;
            }

            $entry->standardOutput .= $buffer;
        };
    }

    /**
     * Flushes completed detached process output in enqueue order.
     *
     * @param OutputInterface $output the output that SHALL receive detached process output
     *
     * @return bool whether at least one detached process output has been flushed
     */
    private function flushDetachedProcessesOutput(OutputInterface $output): bool
    {
        $remainingDetachedProcesses = [];
        $hasFlushedDetachedProcess = false;

        foreach ($this->runningDetachedProcesses as $entry) {
            if ($entry->process->isRunning()) {
                $remainingDetachedProcesses[] = $entry;

                continue;
            }

            $hasFlushedDetachedProcess = true;
            $entry->process->wait();
            $writeDetachedOutput = fn(): bool => $this->writeDetachedOutput(
                $entry->standardOutput,
                $entry->errorOutput,
                $output
            );

            $this->githubActionOutput->group($entry->label, $writeDetachedOutput);
        }

        $this->runningDetachedProcesses = $remainingDetachedProcesses;

        return $hasFlushedDetachedProcess;
    }

    /**
     * Writes buffered detached output to the configured output.
     *
     * @param string $standardOutput
     * @param string $errorOutput
     * @param OutputInterface $output the output that SHALL receive detached process output
     *
     * @return bool always returns true to support grouped callback usage
     */
    private function writeDetachedOutput(string $standardOutput, string $errorOutput, OutputInterface $output): bool
    {
        if ('' !== $standardOutput) {
            $output->write($standardOutput);
        }

        if (
            '' !== $errorOutput
            && $output instanceof ConsoleOutputInterface
        ) {
            $output->getErrorOutput()
                ->write($errorOutput);
        } elseif ('' !== $errorOutput) {
            $output->write($errorOutput);
        }

        return true;
    }

    /**
     * Resolves the label used when presenting queued process output.
     *
     * @param Process $process the queued process instance
     * @param ?string $label the optional label provided by the caller
     *
     * @return string the resolved presentation label
     */
    private function resolveLabel(Process $process, ?string $label = null): string
    {
        if (null !== $label) {
            return $label;
        }

        return 'Running ' . $this->formatProcessCommandLine($process);
    }

    /**
     * Formats the configured process command line without shell escaping noise.
     *
     * @param Process $process the queued process instance
     *
     * @return string the human-readable command line
     */
    private function formatProcessCommandLine(Process $process): string
    {
        $commandLine = $this->getProcessCommandLine($process);

        if (\is_array($commandLine)) {
            return implode(' ', array_map(strval(...), $commandLine));
        }

        return $commandLine;
    }

    /**
     * Reads the raw configured Process command line.
     *
     * Symfony keeps the configured command line in a private property, so the
     * queue reads it reflectively to build a cleaner default label than the
     * shell-escaped output returned by `Process::getCommandLine()`.
     *
     * @param Process $process the queued process instance
     *
     * @return array<int, string>|string
     */
    private function getProcessCommandLine(Process $process): array|string
    {
        self::$commandLineProperty ??= new ReflectionProperty(Process::class, 'commandline');

        if (! self::$commandLineProperty->isInitialized($process)) {
            return 'process';
        }

        /** @var array<int, string>|string $commandLine */
        $commandLine = self::$commandLineProperty->getValue($process);

        return $commandLine;
    }
}
