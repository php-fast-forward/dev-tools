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
     * Creates a new instance of `BootstrapResult`.
     *
     * @param bool $configCreated indicates whether the configuration file was created during bootstrap
     * @param bool $changelogCreated Indicates whether the changelog file was created during bootstrap
     * @param bool $unreleasedCreated Indicates whether the unreleased changelog file was created during bootstrap
     */
    public function __construct(
        public bool $configCreated,
        public bool $changelogCreated,
        public bool $unreleasedCreated,
    ) {}
}
