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

namespace FastForward\DevTools\Console\Output;

/**
 * Enumerates the supported command output formats.
 */
enum OutputFormat: string
{
    case TEXT = 'text';
    case JSON = 'json';

    /**
     * @return string
     */
    public static function defaultValue(): string
    {
        return self::TEXT->value;
    }

    /**
     * @return array
     */
    public static function supportedValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public static function isSupported(string $format): bool
    {
        return \in_array($format, self::supportedValues(), true);
    }
}
