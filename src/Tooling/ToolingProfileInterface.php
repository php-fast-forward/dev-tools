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

namespace FastForward\DevTools\Tooling;

/**
 * Reads explicit project capability selections without installing tools.
 *
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
interface ToolingProfileInterface
{
    /**
     * @return list<string>|null null retains the legacy full suite
     */
    public function read(): ?array;

    /**
     * @param list<string> $capabilities explicit selected capabilities
     * @param bool $dryRun
     */
    public function configure(array $capabilities, bool $dryRun = false): bool;

    /**
     * @return array<string, array{selected: bool, available: bool, missing: list<string>, binaries: array<string, string>}>
     */
    public function status(): array;

    /**
     * @param list<string> $capabilities capabilities required by the current execution plan
     */
    public function assertAvailable(array $capabilities): void;
}
