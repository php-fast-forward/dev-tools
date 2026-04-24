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

namespace FastForward\DevTools\Php;

/**
 * Checks PHP runtime extension availability through PHP's native runtime.
 */
final class Extension implements ExtensionInterface
{
    /**
     * Determines whether a PHP extension is loaded in the current runtime.
     *
     * @param string $name the extension name
     *
     * @return bool true when the extension is loaded
     */
    public function isLoaded(string $name): bool
    {
        return \extension_loaded($name);
    }
}
