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

namespace FastForward\DevTools\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;

final class Plugin implements Capable, PluginInterface
{
    /**
     * @return array
     */
    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => DevToolsCommandProvider::class,
        ];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Add dev-tools script to composer.json if not already present
        $extra = $composer->getPackage()
            ->getExtra() ?? [];

        if (! isset($extra['scripts']['dev-tools'])) {
            $extra['scripts']['dev-tools'] = 'dev-tools';
            $composer->getPackage()
                ->setExtra($extra);
        }
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No deactivation logic needed for this plugin
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No uninstall logic needed for this plugin
    }
}
