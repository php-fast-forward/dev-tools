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

namespace FastForward\DevTools\GitHooks;

/**
 * Renders packaged Git hooks with project-owned configuration paths.
 */
final class HookContentRenderer
{
    /**
     * Placeholder replaced with the durable project-local GrumPHP config path.
     */
    public const string MANAGED_GRUMPHP_CONFIG_PLACEHOLDER = '__DEV_TOOLS_GRUMPHP_CONFIG__';

    /**
     * Renders the hook contents for the active DevTools runtime.
     *
     * @param string $contents the packaged hook contents
     *
     * @return string the rendered hook contents
     */
    public function render(string $contents): string
    {
        return str_replace(self::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER, escapeshellarg('./grumphp.yml'), $contents);
    }
}
