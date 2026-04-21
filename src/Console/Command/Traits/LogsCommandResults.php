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

namespace FastForward\DevTools\Console\Command\Traits;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides reusable success and failure helpers for command logging.
 */
trait LogsCommandResults
{
    use HasCommandLogger;
    use HasGithubActionOutput;

    /**
     * Logs a notice-level command message.
     *
     * @param string $message the notice message
     * @param InputInterface $input the originating command input
     * @param array<string, mixed> $context optional extra log context
     *
     * @return void
     */
    private function notice(string $message, InputInterface $input, array $context = []): void
    {
        $this->getLogger()
            ->notice($message, [
                'input' => $input,
                ...$context,
            ]);
    }

    /**
     * Logs a successful command result and returns a success exit code.
     *
     * @param string $message the success message
     * @param InputInterface $input the originating command input
     * @param array<string, mixed> $context optional extra log context
     * @param string $logLevel the PSR-3 log level used for the successful result
     *
     * @return int
     */
    private function success(
        string $message,
        InputInterface $input,
        array $context = [],
        string $logLevel = LogLevel::INFO,
    ): int {
        $context = [
            'input' => $input,
            ...$context,
        ];

        $this->getLogger()
            ->log($logLevel, $message, $context);

        return Command::SUCCESS;
    }

    /**
     * Logs a failed command result, emits a GitHub Actions error annotation, and returns a failure exit code.
     *
     * @param string $message the failure message
     * @param InputInterface $input the originating command input
     * @param array<string, mixed> $context optional extra log context
     * @param string|null $file the related file path when known
     * @param int|null $line the related line when known
     *
     * @return int
     */
    private function failure(
        string $message,
        InputInterface $input,
        array $context = [],
        ?string $file = null,
        ?int $line = null,
    ): int {
        $this->getLogger()
            ->error($message, [
                'input' => $input,
                ...$context,
            ]);

        if ($this->supportsGithubActionOutput()) {
            $this->getGithubActionOutput()
                ->error($message, $file, $line);
        }

        return Command::FAILURE;
    }
}
