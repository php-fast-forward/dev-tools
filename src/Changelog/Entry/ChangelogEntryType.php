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

namespace FastForward\DevTools\Changelog\Entry;

use InvalidArgumentException;

/**
 * Represents the supported Keep a Changelog entry categories.
 */
enum ChangelogEntryType: string
{
    case Added = 'Added';
    case Changed = 'Changed';
    case Deprecated = 'Deprecated';
    case Removed = 'Removed';
    case Fixed = 'Fixed';
    case Security = 'Security';

    /**
     * Returns the changelog section ordering expected by the renderer.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Added, self::Changed, self::Deprecated, self::Removed, self::Fixed, self::Security];
    }

    /**
     * Resolves a user-provided category value to an enum case.
     *
     * @param string $value the raw category value
     *
     * @return self the resolved changelog entry type
     */
    public static function fromInput(string $value): self
    {
        $normalized = ucfirst(strtolower(trim($value)));

        return self::tryFrom($normalized)
            ?? throw new InvalidArgumentException(\sprintf('Unsupported changelog type "%s".', $value));
    }
}
