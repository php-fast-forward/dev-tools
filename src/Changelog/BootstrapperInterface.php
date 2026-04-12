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
 * Bootstraps repository-local changelog automation artifacts.
 *
 * The BootstrapperInterface defines a contract for bootstrapping changelog automation assets in a given working directory.
 * Implementations of this interface are MUST setup necessary files, configurations, or other resources required to enable
 * changelog automation in a repository. The bootstrap method takes a working directory as input and returns a BootstrapResult
 * indicating the outcome of the bootstrapping process.
 */
interface BootstrapperInterface
{
    /**
     * Bootstraps changelog automation assets in the given working directory.
     *
     * @param string $workingDirectory
     *
     * @return BootstrapResult
     */
    public function bootstrap(string $workingDirectory): BootstrapResult;
}
