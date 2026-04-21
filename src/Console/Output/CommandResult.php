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

/**
 * Carries the normalized output data for one console command execution.
 */
final readonly class CommandResult
{
    /**
     * @param string $status machine-readable command status
     * @param string $message human-readable summary of the result
     * @param array<string, mixed> $context structured context for machine-readable output
     */
    private function __construct(
        public string $status,
        public string $message,
        public array $context = [],
    ) {}

    /**
     * Creates a success result payload.
     *
     * @param string $message human-readable summary of the result
     * @param array<string, mixed> $context structured context for machine-readable output
     */
    public static function success(string $message, array $context = []): self
    {
        return new self('success', $message, $context);
    }

    /**
     * Creates a failure result payload.
     *
     * @param string $message human-readable summary of the result
     * @param array<string, mixed> $context structured context for machine-readable output
     */
    public static function failure(string $message, array $context = []): self
    {
        return new self('failure', $message, $context);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
