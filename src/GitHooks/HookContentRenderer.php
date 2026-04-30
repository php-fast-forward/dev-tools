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

use FastForward\DevTools\Path\DevToolsPathResolver;

/**
 * Renders packaged Git hooks with runtime-specific DevTools hook configuration paths.
 */
final class HookContentRenderer
{
    /**
     * Placeholder replaced with the packaged GrumPHP config path rendered relative to the project when possible.
     */
    public const string MANAGED_GRUMPHP_CONFIG_PLACEHOLDER = '__DEV_TOOLS_GRUMPHP_CONFIG__';

    /**
     * Renders the hook contents for the active DevTools runtime.
     *
     * @param string $contents the packaged hook contents
     * @param string $projectPath the consumer project root that will own the synchronized hook
     *
     * @return string the rendered hook contents
     */
    public function render(string $contents, string $projectPath = ''): string
    {
        return str_replace(
            self::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER,
            escapeshellarg(DevToolsPathResolver::getPackagePathRelativeToProject('grumphp.yml', $projectPath)),
            $contents,
        );
    }
}
