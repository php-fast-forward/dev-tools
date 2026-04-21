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

namespace FastForward\DevTools\Console\Logger;

use Stringable;
use DateTimeInterface;
use Ergebnis\AgentDetector\Detector;
use FastForward\DevTools\Console\Logger\Processor\ContextProcessorInterface;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function Safe\json_encode;

/**
 * Formats PSR-3 log messages for the DevTools console runtime.
 *
 * The logger routes error-related levels to stderr, expands command context
 * through the configured processor, and can switch between tagged text output
 * and structured JSON output depending on CLI flags or detected agent
 * execution.
 */
final readonly class OutputFormatLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * Lists the log levels that MUST be written to the error output stream.
     *
     * @var list<string>
     */
    private const array ERROR_LEVELS = [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY];

    /**
     * Creates a new console logger instance.
     *
     * @param ArgvInput $input the CLI input instance used to inspect runtime options
     * @param ConsoleOutputInterface $output the console output instance used for writing log messages
     * @param ClockInterface $clock provides timestamps for rendered log entries
     * @param Detector $agentDetector detects agent-oriented execution environments
     * @param ContextProcessorInterface $contextProcessor expands command input and output metadata
     * @param GithubActionOutput $githubActionOutput emits GitHub Actions annotations when supported
     */
    public function __construct(
        private ArgvInput $input,
        private ConsoleOutputInterface $output,
        private ClockInterface $clock,
        private Detector $agentDetector,
        private ContextProcessorInterface $contextProcessor,
        private GithubActionOutput $githubActionOutput,
    ) {}

    /**
     * Logs a message at the specified level.
     *
     * This method MUST format the message before writing it to the console.
     * Error-related levels SHALL be directed to the error output stream.
     * All other levels SHALL be directed to the standard output stream.
     *
     * @param mixed $level the log level identifier
     * @param string|Stringable $message the log message, optionally containing PSR-3 placeholders
     * @param array<string, mixed> $context context data used for placeholder interpolation and JSON output
     */
    public function log($level, $message, array $context = []): void
    {
        $context = $this->contextProcessor->process($context);
        $formattedMessage = $this->formatMessage((string) $level, (string) $message, $context);
        $output = $this->output;

        if (\in_array($level, self::ERROR_LEVELS, true)) {
            $output = $this->output->getErrorOutput();
        }

        $this->emitGithubActionAnnotation((string) $level, (string) $message, $context);
        $output->writeln($formattedMessage);
    }

    /**
     * Emits GitHub Actions annotations for supported error levels.
     *
     * @param string $level the normalized log level
     * @param string $message the original message template
     * @param array<string, mixed> $context the processed log context
     *
     * @return void
     */
    private function emitGithubActionAnnotation(string $level, string $message, array $context): void
    {
        if (! \in_array($level, self::ERROR_LEVELS, true)) {
            return;
        }

        $file = isset($context['file']) && \is_string($context['file'])
            ? $context['file']
            : null;
        $line = isset($context['line']) && \is_int($context['line'])
            ? $context['line']
            : null;

        $this->githubActionOutput->error($this->interpolate($message, $context), $file, $line);
    }

    /**
     * Formats a log entry for console output.
     *
     * When JSON output is enabled, the logger MUST return a JSON-encoded
     * representation of the message, level, and context. Otherwise, the
     * message SHALL be interpolated and wrapped with a console tag that uses
     * the log level as both the style name and visual prefix.
     *
     * @param string $level the normalized log level
     * @param string $message the message template to format
     * @param array<string, mixed> $context context values used during formatting
     *
     * @return string the formatted message ready to be written to the console
     */
    private function formatMessage(string $level, string $message, array $context): string
    {
        $timestamp = $this->clock->now()
            ->format(DateTimeInterface::RFC3339);

        if ($this->isJsonOutput()) {
            $flags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;

            if ($this->isPrettyJsonOutput()) {
                $flags |= \JSON_PRETTY_PRINT;
            }

            return json_encode([
                'message' => $message,
                'level' => $level,
                'context' => $context,
                'timestamp' => $timestamp,
            ], $flags);
        }

        $message = $this->interpolate($message, $context);

        return \sprintf('<%s>%s [%s] %s</%s>', $level, $timestamp, strtoupper($level), $message, $level);
    }

    /**
     * Determines whether structured JSON output has been requested.
     *
     * The "--json" and "--pretty-json" options MAY be provided by the caller.
     * When either is present, this method SHALL return true. Otherwise,
     * detected agent environments SHOULD default to JSON output as well.
     *
     * @return bool true when JSON output is enabled; otherwise, false
     */
    private function isJsonOutput(): bool
    {
        if ($this->isPrettyJsonOutput()) {
            return true;
        }

        if ($this->input->hasParameterOption('--json', true)) {
            return true;
        }

        return $this->agentDetector->isAgentPresent($_SERVER);
    }

    /**
     * Determines whether pretty-printed JSON output has been requested.
     */
    private function isPrettyJsonOutput(): bool
    {
        return $this->input->hasParameterOption('--pretty-json', true);
    }

    /**
     * Interpolates context values into PSR-3-style message placeholders.
     *
     * Placeholders in the form "{key}" SHALL be replaced when a matching key
     * exists in the context array and the associated value can be represented
     * safely as text. Scalar values, null, and stringable objects MUST be
     * inserted directly. DateTime values SHALL be formatted using RFC3339.
     * Objects and arrays MUST be converted into descriptive string
     * representations.
     *
     * @param string $message the message containing optional placeholders
     * @param array<string, mixed> $context the context map used for replacement values
     *
     * @return string the interpolated message
     *
     * @author PHP Framework Interoperability Group
     */
    private function interpolate(string $message, array $context): string
    {
        if (! str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];

        foreach ($context as $key => $val) {
            if (null === $val || \is_scalar($val) || $val instanceof Stringable) {
                $replacements[\sprintf('{%s}', $key)] = $val;
            } elseif ($val instanceof DateTimeInterface) {
                $replacements[\sprintf('{%s}', $key)] = $val->format(DateTimeInterface::RFC3339);
            } elseif (\is_object($val)) {
                $replacements[\sprintf('{%s}', $key)] = '[object ' . $val::class . ']';
            } elseif (\is_array($val)) {
                $replacements[\sprintf('{%s}', $key)] = '[' . json_encode(
                    $val,
                    \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES
                ) . ']';
            } else {
                $replacements[\sprintf('{%s}', $key)] = '[' . \gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}
