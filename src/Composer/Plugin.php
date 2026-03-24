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

/**
 * Implements the lifecycle of the Composer dev-tools extension framework.
 * This plugin class MUST initialize and coordinate custom script registrations securely.
 */
final class Plugin implements Capable, PluginInterface
{
    /**
     * Resolves the implemented Composer capabilities structure.
     *
     * This method MUST map the primary capability handlers to custom implementations.
     * It SHALL describe how tools seamlessly integrate into the execution layer.
     *
     * @return array<string, string> the capability mapping configurations
     */
    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => DevToolsCommandProvider::class,
        ];
    }

    /**
     * Handles activation lifecycle events for the Composer session.
     *
     * The method MUST ensure the `dev-tools` script capability exists inside `composer.json` extras.
     * It SHOULD append it if currently missing.
     *
     * @param Composer $composer the primary package configuration instance over Composer
     * @param IOInterface $io interactive communication channels
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
     * Cleans up operations during Composer plugin deactivation events.
     *
     * This method MUST implement the standard Composer lifecycle correctly, even if vacant.
     *
     * @param Composer $composer the primary metadata controller object
     * @param IOInterface $io defined interactions proxy
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No deactivation logic needed for this plugin
    }

    /**
     * Handles final uninstallation processes logically.
     *
     * This method MUST manage cleanup duties per Composer constraints, even if empty.
     *
     * @param Composer $composer system package registry utility
     * @param IOInterface $io execution runtime outputs and inputs proxy interface
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No uninstall logic needed for this plugin
    }
}
