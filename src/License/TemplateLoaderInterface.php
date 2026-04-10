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

namespace FastForward\DevTools\License;

/**
 * Loads license template files from the filesystem.
 *
 * This interface defines the contract for reading license template content
 * based on a template filename provided by the resolver.
 */
interface TemplateLoaderInterface
{
    /**
     * Loads a license template file by its filename.
     *
     * @param string $templateFilename The filename of the template to load
     *
     * @return string The template content
     *
     * @throws RuntimeException if the template file is not found
     */
    public function load(string $templateFilename): string;
}
