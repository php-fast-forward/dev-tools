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

namespace FastForward\DevTools\Environment;

/**
 * Reads process environment variables without binding callers to Composer APIs.
 */
interface EnvironmentInterface
{
    /**
     * Reads an environment variable or, when no name is provided, the current environment map.
     *
     * @param string|null $name the environment variable name, or null to read the current environment map
     * @param string|null $default the value returned when the named variable is not defined
     *
     * @return array<string, string>|string|null the environment map, variable value, or default fallback
     */
    public function get(?string $name = null, ?string $default = null): array|string|null;
}
