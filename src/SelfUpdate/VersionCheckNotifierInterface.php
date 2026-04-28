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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits non-blocking DevTools freshness notices for interactive binary runs.
 */
interface VersionCheckNotifierInterface
{
    /**
     * Warns when a newer stable DevTools version is available.
     *
     * @param OutputInterface $output the command output receiving a non-blocking warning
     */
    public function notify(OutputInterface $output): void;
}
