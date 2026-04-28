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

namespace FastForward\DevTools\Environment;

/**
 * Answers common runtime-environment questions from process environment flags.
 */
interface RuntimeEnvironmentInterface
{
    /**
     * Returns whether a truthy environment flag is enabled.
     *
     * @param string $name the environment variable name
     *
     * @return bool true when the environment variable is enabled
     */
    public function isEnabled(string $name): bool;

    /**
     * Returns whether the current process runs in GitHub Actions.
     */
    public function isGithubActions(): bool;

    /**
     * Returns whether the current process runs in a CI environment.
     */
    public function isCi(): bool;

    /**
     * Returns whether the Composer test suite runtime flag is enabled.
     */
    public function isComposerTestRun(): bool;
}
