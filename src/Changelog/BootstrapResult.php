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

/**
 * Summarizes what happened during changelog bootstrap.
 */
final readonly class BootstrapResult
{
    /**
     * @param bool $configCreated
     * @param bool $changelogCreated
     * @param bool $unreleasedCreated
     */
    public function __construct(
        public bool $configCreated,
        public bool $changelogCreated,
        public bool $unreleasedCreated,
    ) {}
}
