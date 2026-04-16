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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Composer\Capability;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Console\DevTools;

/**
 * Provides a registry of custom dev-tools commands mapped for Composer integration.
 * This capability struct MUST implement the defined `CommandProvider`.
 */
final class DevToolsCommandProvider implements CommandProvider
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        return array_values(array_filter(
            DevTools::create()->all(),
            static fn(object $command): bool => $command instanceof BaseCommand,
        ));
    }
}
