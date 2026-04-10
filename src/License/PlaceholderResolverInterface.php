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
 * Resolves placeholders in license templates with metadata values.
 *
 * This interface defines the contract for replacing template placeholders
 * such as [year], [author], [project] with actual values.
 */
interface PlaceholderResolverInterface
{
    /**
     * Resolves placeholders in a license template with the provided metadata.
     *
     * @param string $template The license template content with placeholders
     * @param array{year?: int, organization?: string, author?: string, project?: string} $metadata The metadata values to use for replacement
     *
     * @return string The template with all resolved placeholders
     */
    public function resolve(string $template, array $metadata): string;
}
