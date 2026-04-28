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

namespace FastForward\DevTools\SelfUpdate;

use FastForward\DevTools\Environment\EnvironmentInterface;
use Throwable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits update warnings while ensuring version checks never block the requested command.
 */
final readonly class VersionCheckNotifier implements VersionCheckNotifierInterface
{
    /**
     * @param VersionCheckerInterface $versionChecker the checker used to resolve latest release metadata
     * @param EnvironmentInterface $environment the environment reader used to skip non-interactive CI checks
     */
    public function __construct(
        private VersionCheckerInterface $versionChecker,
        private EnvironmentInterface $environment,
    ) {}

    /**
     * Warns when a newer stable DevTools version is available.
     *
     * @param OutputInterface $output the command output receiving a non-blocking warning
     */
    public function notify(OutputInterface $output): void
    {
        if ($this->shouldSkipVersionCheck()) {
            return;
        }

        try {
            $result = $this->versionChecker->check();
        } catch (Throwable) {
            return;
        }

        if (! $result instanceof VersionCheckResult || ! $result->isOutdated()) {
            return;
        }

        $output->writeln(\sprintf(
            '<comment>DevTools %s is available; current version is %s. Run "dev-tools self-update" to update.</comment>',
            $result->getLatestVersion(),
            $result->getCurrentVersion(),
        ));
    }

    /**
     * Returns whether DevTools SHOULD skip the best-effort version check.
     */
    private function shouldSkipVersionCheck(): bool
    {
        foreach (['FAST_FORWARD_SKIP_VERSION_CHECK', 'GITHUB_ACTIONS', 'CI'] as $name) {
            if ($this->isTruthy($this->environment->get($name, ''))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether an environment value represents an enabled flag.
     *
     * @param string|null $value the environment value to inspect
     */
    private function isTruthy(?string $value): bool
    {
        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
