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

namespace FastForward\DevTools\Console\Output;

use Composer\Util\Platform;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Emits GitHub Actions workflow commands when the current environment supports them.
 */
final class GithubActionOutput
{
    private ?string $currentGroup = null;

    /**
     * @param ConsoleOutputInterface $output the console output used to emit workflow commands
     */
    public function __construct(
        private readonly ConsoleOutputInterface $output,
    ) {}

    /**
     * Closes any open group before the output instance is destroyed.
     */
    public function __destruct()
    {
        if (null !== $this->currentGroup) {
            $this->endGroup();
        }
    }

    /**
     * Emits an error annotation.
     *
     * @param string $message the annotation message
     * @param string|null $file the related file when known
     * @param int|null $line the related line when known
     * @param int|null $column the related column when known
     *
     * @return void
     */
    public function error(string $message, ?string $file = null, ?int $line = null, ?int $column = null): void
    {
        $properties = [];

        if (null !== $file) {
            $properties['file'] = $file;
        }

        if (null !== $line) {
            $properties['line'] = (string) $line;
        }

        if (null !== $column) {
            $properties['col'] = (string) $column;
        }

        $this->emit('error', $message, $properties);
    }

    /**
     * Emits a warning annotation.
     *
     * @param string $message the annotation message
     * @param string|null $file the related file when known
     * @param int|null $line the related line when known
     * @param int|null $column the related column when known
     *
     * @return void
     */
    public function warning(string $message, ?string $file = null, ?int $line = null, ?int $column = null): void
    {
        $properties = [];

        if (null !== $file) {
            $properties['file'] = $file;
        }

        if (null !== $line) {
            $properties['line'] = (string) $line;
        }

        if (null !== $column) {
            $properties['col'] = (string) $column;
        }

        $this->emit('warning', $message, $properties);
    }

    /**
     * Emits a notice annotation.
     *
     * @param string $message the annotation message
     * @param string|null $file the related file when known
     * @param int|null $line the related line when known
     * @param int|null $column the related column when known
     *
     * @return void
     */
    public function notice(string $message, ?string $file = null, ?int $line = null, ?int $column = null): void
    {
        $properties = [];

        if (null !== $file) {
            $properties['file'] = $file;
        }

        if (null !== $line) {
            $properties['line'] = (string) $line;
        }

        if (null !== $column) {
            $properties['col'] = (string) $column;
        }

        $this->emit('notice', $message, $properties);
    }

    /**
     * Emits a debug log line.
     *
     * @param string $message the debug message
     *
     * @return void
     */
    public function debug(string $message): void
    {
        $this->emit('debug', $message);
    }

    /**
     * Starts a collapsible log group.
     *
     * @param string $title the group title
     *
     * @return void
     */
    public function startGroup(string $title): void
    {
        if (null !== $this->currentGroup) {
            $this->endGroup();
        }

        $this->currentGroup = $title;
        $this->emit('group', $title);
    }

    /**
     * Ends the current collapsible log group.
     *
     * @return void
     */
    public function endGroup(): void
    {
        if (null === $this->currentGroup) {
            return;
        }

        $this->currentGroup = null;
        $this->emit('endgroup');
    }

    /**
     * Runs a callback wrapped inside a GitHub Actions log group.
     *
     * @template TResult
     *
     * @param string $title the group title
     * @param callable(): TResult $callback the callback to execute within the group
     *
     * @return TResult
     */
    public function group(string $title, callable $callback): mixed
    {
        $this->startGroup($title);

        try {
            return $callback();
        } finally {
            $this->endGroup();
        }
    }

    /**
     * @param string $command the GitHub workflow command name
     * @param string $message the command message
     * @param array<string, string> $properties the optional command properties
     *
     * @return void
     */
    private function emit(string $command, string $message = '', array $properties = []): void
    {
        if (! $this->supportsWorkflowCommands()) {
            return;
        }

        $command = $this->escapeProperty($command);
        $message = $this->escapeData($message);

        if ([] === $properties) {
            $this->output->write(\sprintf('::%s::%s', $command, $message));

            return;
        }

        $properties = array_map($this->escapeProperty(...), $properties);

        $serializedProperties = [];

        foreach ($properties as $name => $value) {
            $serializedProperties[] = \sprintf('%s=%s', $name, $value);
        }

        $this->output->write(\sprintf('::%s %s::%s', $command, implode(',', $serializedProperties), $message));
    }

    /**
     * @return bool true when the current environment supports GitHub workflow commands
     */
    private function supportsWorkflowCommands(): bool
    {
        return (bool) Platform::getEnv('GITHUB_ACTIONS')
            && ! (bool) Platform::getEnv('COMPOSER_TESTS_ARE_RUNNING');
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function escapeData(string $data): string
    {
        $data = str_replace('%', '%25', $data);
        $data = str_replace("\r", '%0D', $data);

        return str_replace("\n", '%0A', $data);
    }

    /**
     * @param string $property
     *
     * @return string
     */
    private function escapeProperty(string $property): string
    {
        $property = str_replace('%', '%25', $property);
        $property = str_replace("\r", '%0D', $property);
        $property = str_replace("\n", '%0A', $property);
        $property = str_replace(':', '%3A', $property);

        return str_replace(',', '%2C', $property);
    }
}
