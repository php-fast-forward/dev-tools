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
            ScriptEvents::POST_INSTALL_CMD => 'runSyncCommand',
            ScriptEvents::POST_UPDATE_CMD => 'runSyncCommand',
        ];
    }

    /**
     * Handles the automated script installation.
     *
     * This method MUST execute the `dev-tools:sync` command after relevant Composer operations to ensure
     * the development tools are correctly synchronized with the current project state.
     *
     * @param Event $event the Composer script event context
     *
     * @return void
     */
    public function runSyncCommand(Event $event): void
    {
        $event->getComposer()
            ->getLoop()
            ->getProcessExecutor()
            ->execute('vendor/bin/dev-tools dev-tools:sync');
    }

    /**
     * Handles activation lifecycle events for the Composer session.
     *
     * This method MUST adhere to the standard Composer plugin activation protocol, even if no specific logic is required.
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
