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
use FastForward\DevTools\Console\Logger\Processor\ContextProcessorInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function Safe\json_encode;

/**
 * Provides formatted console logging for command-line execution contexts.
 *
 * This logger writes messages to the Symfony console output streams and SHALL
 * route error-related log levels to stderr. Non-error log levels SHALL be
 * written to the standard output stream. When the "--json" option is present,
 * this logger MUST serialize the message payload as JSON.
 *
 * The implementation SHOULD be used in CLI environments where an ArgvInput and
 * a ConsoleOutputInterface are available. Callers MAY rely on placeholder
 * interpolation behavior compatible with PSR-3 expectations for scalar values,
 * stringable values, dates, arrays, and objects.
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
     * The provided input SHALL be inspected to determine whether JSON output
     * has been requested. The provided output SHALL be used as the primary
     * destination for normal log messages, while its error stream SHALL be
     * used for error-level messages.
     *
     * @param ArgvInput $input the CLI input instance used to inspect runtime options
     * @param ConsoleOutputInterface $output the console output instance used for writing log messages
     * @param ClockInterface $clock
     * @param ContextProcessorInterface $contextProcessor expands command input and output metadata
     */
    public function __construct(
        private ArgvInput $input,
        private ConsoleOutputInterface $output,
        private ClockInterface $clock,
        private ContextProcessorInterface $contextProcessor,
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

        $output->writeln($formattedMessage);
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
            return json_encode(
                [
                    'message' => $message,
                    'level' => $level,
                    'context' => $context,
                    'timestamp' => $timestamp,
                ],
                \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES
            );
        }

        $message = $this->interpolate($message, $context);

        return \sprintf('<%s>%s [%s] %s</%s>', $level, $timestamp, strtoupper($level), $message, $level);
    }

    /**
     * Determines whether JSON output has been requested.
     *
     * The "--json" option MAY be provided by the caller. When present, this
     * method SHALL return true. Otherwise, it MUST return false.
     *
     * @return bool true when JSON output is enabled; otherwise, false
     */
    private function isJsonOutput(): bool
    {
        return $this->input->hasParameterOption('--json', true);
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
