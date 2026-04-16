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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\PhpUnit\Event\TestSuite;

use FastForward\DevTools\PhpUnit\Event\EventTracer;
use Joli\JoliNotif\DefaultNotifier;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierInterface;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\Started;
use Symfony\Component\Console\Helper\Helper;

/**
 * Sends a desktop notification when the PHPUnit execution finishes.
 *
 * This subscriber MUST summarize the current PHPUnit run using the counters
 * collected by the shared event tracer and SHALL dispatch a concise desktop
 * notification through the configured notifier implementation.
 *
 * The generated notification MUST preserve the effective behavior currently
 * expected by the application:
 * - successful runs SHALL report only the total number of executed tests,
 *   followed by expanded telemetry details;
 * - unsuccessful runs SHALL report the number of passed tests together with
 *   the relevant failure and error counters, followed by expanded telemetry
 *   details.
 *
 * When process forking support is available, notification delivery SHOULD be
 * delegated to a child process so the main PHPUnit process MAY continue
 * shutting down without waiting for the desktop notification transport to
 * complete.
 *
 * @codeCoverageIgnore
 */
final readonly class JoliNotifExecutionFinishedSubscriber implements ExecutionFinishedSubscriber
{
    /**
     * Creates a new execution-finished subscriber instance.
     *
     * The provided tracer MUST contain the event history collected during the
     * current PHPUnit run so this subscriber can derive an accurate summary.
     * When no notifier is explicitly provided, the default JoliNotif notifier
     * SHALL be used.
     *
     * @param EventTracer $tracer the event tracer used to inspect recorded
     *                            PHPUnit events and derive notification data
     * @param NotifierInterface $notifier the notifier responsible for
     *                                    dispatching the desktop notification
     */
    public function __construct(
        private EventTracer $tracer,
        private NotifierInterface $notifier = new DefaultNotifier(),
    ) {}

    /**
     * Handles the PHPUnit execution finished event.
     *
     * This method MUST build the final notification payload using the computed
     * title, execution summary, and telemetry details.
     * When process forking is available, the parent process SHALL return
     * immediately after a successful fork, and the child process SHALL send
     * the notification and terminate explicitly. When forking support is not
     * available, or the fork attempt fails, the notification MUST still be
     * delivered synchronously so functional behavior remains unchanged.
     *
     * @param ExecutionFinished $event the emitted PHPUnit execution finished
     *                                 event
     *
     * @return void
     */
    public function notify(ExecutionFinished $event): void
    {
        $notification = (new Notification())
            ->setTitle($this->getTitle())
            ->setBody($this->getBody() . $this->getTelemetryBody($event))
            ->setIcon(\dirname(__DIR__, 4) . '/resources/phpunit.avif');

        $pid = \function_exists('pcntl_fork') ? pcntl_fork() : -1;

        if ($pid > 0) {
            return;
        }

        $this->notifier->send($notification);

        if (0 === $pid) {
            exit(0);
        }
    }

    /**
     * Builds the telemetry block appended to the notification body.
     *
     * PHPUnit telemetry MUST be expanded into explicit, human-readable metrics
     * instead of being displayed as a compact raw string. This method SHALL
     * expose the most relevant execution data in a readable multi-line block,
     * including runtime, memory consumption, suite progression, prepared test
     * count, and the overall assertion status.
     *
     * @param ExecutionFinished $event the PHPUnit execution finished event
     *
     * @return string the formatted telemetry block, including its leading line
     *                breaks
     */
    private function getTelemetryBody(ExecutionFinished $event): string
    {
        $telemetryInfo = $event->telemetryInfo();

        $lines = [
            'Telemetry',
            \sprintf('- Runtime: %s', Helper::formatTime($telemetryInfo->durationSinceStart()->seconds())),
            \sprintf('- Peak memory: %s', Helper::formatMemory($telemetryInfo->peakMemoryUsage()->bytes())),
            \sprintf('- Current memory: %s', Helper::formatMemory($telemetryInfo->memoryUsage()->bytes())),
            \sprintf(
                '- Test suites: %d/%d finished',
                $this->tracer->count(Finished::class),
                $this->tracer->count(Started::class)
            ),
            \sprintf('- Tests prepared: %d', $this->tracer->count(Prepared::class)),
            \sprintf('- Assertions mode: %s', $this->hasIssues() ? 'completed with issues' : 'all checks passed'),
        ];

        return "\n\n" . implode("\n", $lines);
    }

    /**
     * Builds the notification title for the current execution result.
     *
     * Successful executions MUST produce a success-oriented title. Executions
     * containing at least one failure or error SHALL produce a failure-oriented
     * title.
     *
     * @return string the formatted notification title
     */
    private function getTitle(): string
    {
        if (! $this->hasIssues()) {
            return '✅ Test Suite Passed';
        }

        return '❌ Test Suite Failed';
    }

    /**
     * Builds the main notification body for the current execution result.
     *
     * Successful executions MUST produce a minimal body containing only the
     * total number of executed tests. Executions with failures or errors SHALL
     * produce a body containing the number of passed tests together with the
     * relevant failure and error counters.
     *
     * @return string the formatted notification body
     */
    private function getBody(): string
    {
        if (! $this->hasIssues()) {
            return $this->getSuccessBody();
        }

        return $this->getFailedBody();
    }

    /**
     * Determines whether the current test run contains at least one failure or error.
     *
     * @return bool true when the execution contains failures or errors;
     *              otherwise false
     */
    private function hasIssues(): bool
    {
        return 0 < ($this->tracer->count(Errored::class) + $this->tracer->count(Failed::class));
    }

    /**
     * Builds the notification body for a fully successful test run.
     *
     * The success body SHOULD remain intentionally brief because additional
     * diagnostic detail is unnecessary when all tests pass.
     *
     * @return string the formatted success body
     */
    private function getSuccessBody(): string
    {
        return \sprintf(
            '%d test%s passed',
            $this->tracer->count(Prepared::class),
            1 === $this->tracer->count(Prepared::class) ? '' : 's'
        );
    }

    /**
     * Builds the notification body for a test run containing failures or errors.
     *
     * The failed body MUST include the total number of passed tests and SHALL
     * include failure and error counters only when those counters are greater
     * than zero.
     *
     * @return string the formatted failure body
     */
    private function getFailedBody(): string
    {
        $body = [
            \sprintf(
                '%d of %d test%s passed',
                $this->getPassedTests(),
                $this->tracer->count(Prepared::class),
                1 === $this->tracer->count(Prepared::class) ? '' : 's',
            ),
        ];

        if (0 < $this->tracer->count(Failed::class)) {
            $body[] = \sprintf(
                '%d failure%s',
                $this->tracer->count(Failed::class),
                1 === $this->tracer->count(Failed::class) ? '' : 's'
            );
        }

        if (0 < $this->tracer->count(Errored::class)) {
            $body[] = \sprintf(
                '%d error%s',
                $this->tracer->count(Errored::class),
                1 === $this->tracer->count(Errored::class) ? '' : 's'
            );
        }

        return implode("\n", $body);
    }

    /**
     * Calculates the number of tests that completed successfully.
     *
     * The returned value MUST NEVER be negative, even if the collected event
     * counters become inconsistent for any reason.
     *
     * @return int the number of successfully completed tests
     */
    private function getPassedTests(): int
    {
        return max(
            0,
            $this->tracer->count(Prepared::class) - ($this->tracer->count(Errored::class) + $this->tracer->count(
                Failed::class
            ))
        );
    }
}
