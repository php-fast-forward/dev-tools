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

use FastForward\DevTools\Command\AbstractCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use FastForward\DevTools\Command\CodeStyleCommand;
use FastForward\DevTools\Command\DocsCommand;
use FastForward\DevTools\Command\PhpDocCommand;
use FastForward\DevTools\Command\RefactorCommand;
use FastForward\DevTools\Command\ReportsCommand;
use FastForward\DevTools\Command\StandardsCommand;
use FastForward\DevTools\Command\TestsCommand;

final class DevToolsCommandProvider implements CommandProviderCapability
{
    /**
     * @return AbstractCommand[]
     */
    public function getCommands()
    {
        return [
            new CodeStyleCommand(),
            new RefactorCommand(),
            new TestsCommand(),
            new PhpDocCommand(),
            new DocsCommand(),
            new StandardsCommand(),
            new ReportsCommand(),
        ];
    }
}
