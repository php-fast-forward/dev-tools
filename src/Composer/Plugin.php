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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;

/**
 * Implements the lifecycle of the Composer dev-tools extension framework.
 * This plugin class MUST initialize and coordinate custom script registrations securely.
 */
final class Plugin implements Capable, EventSubscriberInterface, PluginInterface
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
     * Retrieves the comprehensive map of events this listener SHALL handle.
     *
     * This method MUST define the lifecycle triggers for script installation and
     * synchronization during Composer package operations.
     *
     * @return array<string, string> the event mapping registry
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
        ];
    }

    /**
     * Handles the automated script installation.
     *
     * This method MUST be triggered by `POST_INSTALL_CMD` and SHALL delegate
     * the actual work to the `installScripts` utility.
     *
     * @param Event $event the Composer script event context
     *
     * @return void
     */
    public function onPostInstall(Event $event): void
    {
        $event->getComposer()
            ->getEventDispatcher()
            ->dispatchScript('dev-tools:install', true);
    }

    /**
     * Handles the automated script synchronization after updates.
     *
     * This method MUST be triggered by `POST_UPDATE_CMD` and SHALL ensure
     * that all development scripts are correctly aligned in the root configuration.
     *
     * @param Event $event the Composer script event context
     *
     * @return void
     */
    public function onPostUpdate(Event $event): void
    {
        $event->getComposer()
            ->getEventDispatcher()
            ->dispatchScript('dev-tools:install', true);
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
        // No activation logic needed for this plugin
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
