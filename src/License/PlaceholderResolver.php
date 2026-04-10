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

use function Safe\preg_replace;

final class PlaceholderResolver implements PlaceholderResolverInterface
{
    /**
     * @param array{year?: int, organization?: string, author?: string, project?: string} $metadata
     * @param string $template
     */
    public function resolve(string $template, array $metadata): string
    {
        $replacements = [
            '{{ year }}' => (string) ($metadata['year'] ?? date('Y')),
            '{{ organization }}' => $metadata['organization'] ?? '',
            '{{ author }}' => $metadata['author'] ?? '',
            '{{ project }}' => $metadata['project'] ?? '',
            '{{ copyright_holder }}' => $metadata['organization'] ?? $metadata['author'] ?? '',
        ];

        $result = $template;

        foreach ($replacements as $placeholder => $value) {
            $result = str_replace($placeholder, $value, $result);
        }

        $result = preg_replace('/\{\{\s*\w+\s*\}\}/', '', $result);

        $result = preg_replace('/\n{3,}/', "\n\n", $result);

        return trim((string) $result);
    }
}
