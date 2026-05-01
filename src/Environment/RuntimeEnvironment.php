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

use Ergebnis\AgentDetector\Detector;

/**
 * Resolves common runtime-environment flags used by DevTools integrations.
 */
final readonly class RuntimeEnvironment implements RuntimeEnvironmentInterface
{
    /**
     * @param EnvironmentInterface $environment reads raw process environment variables
     * @param Detector $agentDetector detects known AI-agent environment markers
     */
    public function __construct(
        private EnvironmentInterface $environment,
        private Detector $agentDetector,
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
     * Returns whether the current process runs inside the Composer or PHPUnit test runtime.
     */
    public function isComposerTestRun(): bool
    {
        if (\defined('PHPUNIT_COMPOSER_INSTALL')) {
            return true;
        }

        return $this->isEnabled('COMPOSER_TESTS_ARE_RUNNING');
    }

    /**
     * Returns whether the current process exposes known AI-agent environment markers.
     */
    public function isAgentPresent(): bool
    {
        $environment = $this->environment->get();

        if (! \is_array($environment)) {
            return false;
        }

        return $this->agentDetector->isAgentPresent($environment);
    }
}
