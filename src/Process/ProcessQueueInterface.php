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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Defines a queue responsible for collecting and executing process instances.
 *
 * Implementations MUST preserve the execution contract defined by this interface
 * and SHALL provide deterministic behavior for adding queued processes and
 * running them.
 *
 * Queued processes MAY be configured to run detached from the main blocking
 * flow and MAY also be configured to not affect the final execution result.
 * These behaviors are independent and MUST be interpreted according to the
 * method contract declared below.
 */
interface ProcessQueueInterface
{
    /**
     * Exit code indicating successful queue execution.
     *
     * This constant SHALL be returned when all queued processes that affect the
     * final result complete successfully. Processes configured to ignore
     * failures MUST NOT prevent this value from being returned.
     *
     * @var int
     */
    public const SUCCESS = 0;

    /**
     * Exit code indicating a queue failure.
     *
     * This constant SHALL be returned when at least one queued process that
     * affects the final result fails during startup or execution. Processes
     * configured to ignore failures MUST NOT cause this value to be returned.
     *
     * @var int
     */
    public const FAILURE = 1;

    /**
     * Adds a process to the queue.
     *
     * The provided process MUST be accepted for later execution by the queue.
     *
     * When $ignoreFailure is set to true, a failure of the process MUST NOT
     * affect the final status code returned by run(). Implementations SHOULD
     * still report or record such a failure when that information is useful
     * for diagnostics.
     *
     * When $detached is set to true, the process SHALL be started without
     * blocking the execution of subsequent queue entries. A detached process
     * MAY continue running after the queue advances to the next entry.
     *
     * Implementations MUST NOT mutate the semantic intent of the supplied
     * process in a way that would make its execution meaningfully different
     * from what the caller configured before enqueueing it.
     *
     * @param Process $process the process instance that SHALL be added to the queue
     * @param bool $ignoreFailure indicates whether a failure of this process MUST NOT affect the final queue result
     * @param bool $detached indicates whether this process SHALL be started without blocking the next queued process
     */
    public function add(Process $process, bool $ignoreFailure = false, bool $detached = false): void;

    /**
     * Runs the queued processes and returns the resulting status code.
     *
     * Implementations MUST return an integer exit status representing the
     * overall execution result of the queue.
     *
     * Failures from processes added with $ignoreFailure set to true MUST NOT be
     * reflected in the final returned status code.
     *
     * The returned status code SHOULD represent the aggregated outcome of all
     * queued processes whose failures are not configured to be ignored.
     *
     * @param ?OutputInterface $output the output interface to which process output and diagnostics MAY be written
     *
     * @return int the final exit status code produced by the queue execution
     */
    public function run(?OutputInterface $output = null): int;

    /**
     * Waits for all detached processes to finish execution.
     *
     * Implementations MUST block the execution thread until all previously
     * started detached processes complete. This ensures the main process
     * does not exit prematurely, preventing detached children from being
     * abruptly terminated.
     *
     * @param ?OutputInterface $output the output interface to which process output and diagnostics MAY be written
     */
    public function wait(?OutputInterface $output = null): void;
}
