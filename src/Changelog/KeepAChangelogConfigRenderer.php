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

namespace FastForward\DevTools\Changelog;

use function implode;

/**
 * Renders the repository-local keep-a-changelog configuration file.
 */
final readonly class KeepAChangelogConfigRenderer
{
    /**
     * @return string
     */
    public function render(): string
    {
        return implode("\n", [
            '[defaults]',
            'changelog_file = CHANGELOG.md',
            'provider = github',
            'remote = origin',
            '',
            '[providers]',
            'github[class] = Phly\KeepAChangelog\Provider\GitHub',
            '',
        ]);
    }
}
