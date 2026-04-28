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
 * Runs the Composer command that updates the installed DevTools package.
 */
interface SelfUpdateRunnerInterface
{
    /**
     * Updates the installed DevTools package.
     *
     * @param bool $global whether the update should target Composer's global project
     * @param OutputInterface $output the command output used by the update process
     *
     * @return int the Composer process status code
     */
    public function update(bool $global, OutputInterface $output): int;
}
