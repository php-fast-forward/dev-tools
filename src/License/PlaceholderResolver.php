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

use Psr\Clock\ClockInterface;

use function Safe\preg_replace;

/**
 * Resolves placeholders in license templates with metadata values.
 *
 * This class replaces placeholders like {{ year }}, {{ author }}, {{ project }},
 * {{ organization }}, and {{ copyright_holder }} with values from metadata.
 * Unresolved placeholders are removed and excess newlines are normalized.
 */
final readonly class PlaceholderResolver implements PlaceholderResolverInterface
{
    /**
     * @param ClockInterface $clock
     */
    public function __construct(
        private ClockInterface $clock,
    ) {}

    /**
     * Resolves placeholders in a license template with the provided metadata.
     *
     * Supported placeholders:
     * - {{ year }} - The copyright year (defaults to current year)
     * - {{ organization }} - The organization or vendor name
     * - {{ author }} - The primary author name or email
     * - {{ project }} - The project/package name
     * - {{ copyright_holder }} - Organization or author (organization takes precedence)
     *
     * Unmatched placeholders are removed, and consecutive blank lines are normalized.
     *
     * @param string $template The license template content with placeholders
     * @param array{year?: int, organization?: string, author?: string, project?: string} $metadata The metadata values to use for replacement
     *
     * @return string The template with all resolved placeholders
     */
    public function resolve(string $template, array $metadata): string
    {
        $now = $this->clock->now();

        $replacements = [
            '{{ year }}' => (string) ($metadata['year'] ?? $now->format('Y')),
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
