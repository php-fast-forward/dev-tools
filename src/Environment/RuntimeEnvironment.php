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
 * Resolves common runtime-environment flags used by DevTools integrations.
 */
final readonly class RuntimeEnvironment implements RuntimeEnvironmentInterface
{
    /**
     * @param EnvironmentInterface $environment reads raw process environment variables
     */
    public function __construct(
        private EnvironmentInterface $environment,
    ) {}

    /**
     * Returns whether a truthy environment flag is enabled.
     *
     * @param string $name the environment variable name
     */
    public function isEnabled(string $name): bool
    {
        return \in_array(strtolower((string) $this->environment->get($name, '')), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Returns whether the current process runs in GitHub Actions.
     */
    public function isGithubActions(): bool
    {
        return $this->isEnabled('GITHUB_ACTIONS');
    }

    /**
     * Returns whether the current process runs in a CI environment.
     */
    public function isCi(): bool
    {
        if ($this->isGithubActions()) {
            return true;
        }

        return $this->isEnabled('CI');
    }

    /**
     * Returns whether the Composer test suite runtime flag is enabled.
     */
    public function isComposerTestRun(): bool
    {
        return $this->isEnabled('COMPOSER_TESTS_ARE_RUNNING');
    }
}
