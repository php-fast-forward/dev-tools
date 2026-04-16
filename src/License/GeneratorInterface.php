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
 * Generates LICENSE files from composer.json metadata.
 *
 * This interface defines the contract for generating license files
 * by reading composer.json and producing appropriate license content.
 */
interface GeneratorInterface
{
    /**
     * Generates a LICENSE file at the specified path.
     *
     * Reads the license from composer.json, validates it's supported,
     * loads the appropriate template, resolves placeholders, and writes
     * the LICENSE file to the target path.
     *
     * @param string $targetPath The full path where the LICENSE file should be written
     *
     * @return string|null The generated license content, or null if generation failed
     */
    public function generate(string $targetPath): ?string;
}
