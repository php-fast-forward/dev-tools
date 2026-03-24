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

namespace FastForward\DevTools;

use Override;
use Composer\Console\Application;
use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;

final class DevTools extends Application
{
    /**
     * @param CommandProvider|null $commandProvider
     */
    public function __construct(
        private readonly ?CommandProvider $commandProvider = new DevToolsCommandProvider(),
    ) {
        parent::__construct('Fast Forward Dev Tools');
        $this->setDefaultCommand('standards');
    }

    /**
     * @return array
     */
    #[Override]
    protected function getDefaultCommands(): array
    {
        return array_merge($this->commandProvider->getCommands(), [
            new HelpCommand(),
            new ListCommand(),
            new CompleteCommand(),
            new DumpCompletionCommand(),
        ]);
    }
}
