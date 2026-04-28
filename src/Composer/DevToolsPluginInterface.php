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

namespace FastForward\DevTools\Composer;

use Composer\Plugin\PluginInterface;

/**
 * Defines DevTools-specific Composer plugin conventions.
 */
interface DevToolsPluginInterface extends PluginInterface
{
    public const array COMPOSER_COMMAND_NAMES = [
        '_complete',
        'about',
        'archive',
        'audit',
        'browse',
        'bump',
        'cc',
        'check-platform-reqs',
        'clear-cache',
        'clearcache',
        'completion',
        'config',
        'create-project',
        'depends',
        'diagnose',
        'dump-autoload',
        'dumpautoload',
        'exec',
        'fund',
        'global',
        'help',
        'home',
        'i',
        'info',
        'init',
        'install',
        'licenses',
        'list',
        'outdated',
        'prohibits',
        'r',
        'reinstall',
        'remove',
        'repo',
        'repository',
        'require',
        'rm',
        'run',
        'run-script',
        'search',
        'self-update',
        'selfupdate',
        'show',
        'status',
        'suggests',
        'u',
        'uninstall',
        'update',
        'upgrade',
        'validate',
        'why',
        'why-not',
    ];

    /**
     * Detects whether a command name or alias is already registered in Composer's command surface.
     *
     * @param string|null $name the command name or alias being evaluated
     */
    public function isRegisteredCommand(?string $name): bool;
}
