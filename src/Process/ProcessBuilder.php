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

use FastForward\DevTools\Path\DevToolsPathResolver;
use Symfony\Component\Process\Process;

/**
 * Builds immutable process definitions from a command and a collection of arguments.
 *
 * This builder SHALL preserve previously supplied arguments by returning a new
 * instance on each mutation-like operation. Implementations of this concrete
 * builder MUST keep argument ordering stable so that the generated process
 * reflects the exact sequence in which arguments were provided.
 */
final readonly class ProcessBuilder implements ProcessBuilderInterface
{
    private const string NO_LOGO_ARGUMENT = '--no-logo';

    /**
     * Creates a new immutable process builder instance.
     *
     * The provided arguments SHALL be stored in the same order in which they are
     * received and MUST be used when building the final process instance.
     *
     * @param list<string> $arguments the arguments already collected by the builder
     */
    public function __construct(
        private array $arguments = [],
    ) {}

    /**
     * Returns a new builder instance with an additional argument appended.
     *
     * When a value is provided, the argument SHALL be normalized to the
     * "{argument}={value}" format before being appended. When no value is
     * provided, the raw argument token MUST be appended as-is.
     *
     * This method MUST NOT mutate the current builder instance and SHALL return
     * a new instance containing the accumulated arguments.
     *
     * @param string $argument the argument name or token to append
     * @param string|null $value the optional value associated with the argument
     *
     * @return ProcessBuilderInterface a new builder instance containing the appended argument
     */
    public function withArgument(string $argument, ?string $value = null): ProcessBuilderInterface
    {
        if (null !== $value) {
            $argument = \sprintf('%s=%s', $argument, $value);
        }

        return new self([...$this->arguments, $argument]);
    }

    /**
     * Returns the arguments currently collected by the builder.
     *
     * The returned list SHALL preserve insertion order and MAY be used for
     * inspection, debugging, or testing purposes.
     *
     * @return list<string> the collected process arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Builds a process instance for the specified command.
     *
     * The command string SHALL be split into tokens using a space separator and
     * combined with all previously collected builder arguments. The resulting
     * process MUST preserve the final token order exactly as assembled by this
     * method.
     *
     * @param string|array $command the base command used to initialize the process
     *
     * @return Process the configured process instance ready for execution
     */
    public function build(string|array $command): Process
    {
        if (\is_array($command)) {
            $command = array_values($command);
        }

        if (\is_string($command)) {
            $command = explode(' ', $command);
        }

        if ($this->shouldAddLogoSuppressionArgument($command)) {
            $command = $this->prependLogoSuppressionArgument($command);
        }

        return new Process(command: [...$command, ...$this->arguments], timeout: 0);
    }

    /**
     * @param list<string> $command
     */
    private function shouldAddLogoSuppressionArgument(array $command): bool
    {
        if (\in_array(self::NO_LOGO_ARGUMENT, $this->arguments, true)) {
            return false;
        }

        if ([] === $command) {
            return false;
        }

        $binary = str_replace('\\', '/', $command[0]);
        $packageBinaryPath = str_replace('\\', '/', DevToolsPathResolver::getBinaryPath());

        return $binary === $packageBinaryPath;
    }

    /**
     * @param list<string> $command
     *
     * @return list<string>
     */
    private function prependLogoSuppressionArgument(array $command): array
    {
        if ([] === $command) {
            return $command;
        }

        $binary = array_shift($command);

        return [$binary, self::NO_LOGO_ARGUMENT, ...$command];
    }
}
