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

use Throwable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits update warnings while ensuring version checks never block the requested command.
 */
final readonly class VersionCheckNotifier implements VersionCheckNotifierInterface
{
    /**
     * @param VersionCheckerInterface $versionChecker the checker used to resolve latest release metadata
     */
    public function __construct(
        private VersionCheckerInterface $versionChecker,
    ) {}

    /**
     * Warns when a newer stable DevTools version is available.
     *
     * @param OutputInterface $output the command output receiving a non-blocking warning
     */
    public function notify(OutputInterface $output): void
    {
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
}
