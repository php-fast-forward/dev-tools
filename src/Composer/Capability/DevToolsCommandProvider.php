<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Composer\Capability;

use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Psr\Container\Container;

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
        return Container::get(DevTools::class)->getCommands();
    }
}
