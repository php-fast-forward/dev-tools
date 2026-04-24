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

namespace FastForward\DevTools\Changelog;

use function Safe\preg_replace;
use function Safe\preg_match;

/**
 * Normalizes minimal changelog entry messages for Dependabot pull requests.
 */
final readonly class DependabotChangelogEntryMessageResolver
{
    /**
     * @param string $title
     * @param int $pullRequestNumber
     *
     * @return string
     */
    public function resolve(string $title, int $pullRequestNumber): string
    {
        $message = preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);
        $message = rtrim($message, " \t\n\r\0\x0B.");

        if (1 === preg_match('/\(#\d+\)$/', $message)) {
            return $message;
        }

        return \sprintf('%s (#%d)', $message, $pullRequestNumber);
    }
}
