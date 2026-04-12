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

use function Safe\preg_match;
use function Safe\preg_replace;
use function str_contains;
use function trim;
use function ucfirst;

/**
 * Classifies conventional and free-form commit subjects into changelog buckets.
 */
final readonly class CommitClassifier implements CommitClassifierInterface
{
    /**
     * Classifies a commit subject into a changelog section based on conventional prefixes and keywords.
     *
     * @param string $subject commit subject to classify
     *
     * @return string Changelog section name (e.g., "Added", "Changed", "Deprecated", "Removed", "Fixed", "Security", or "Uncategorized").
     */
    public function classify(string $subject): string
    {
        $subject = trim($subject);

        if (0 !== preg_match('/\b(security|cve|vulnerability|xss|csrf)\b/i', $subject)) {
            return 'Security';
        }

        if (preg_match('/^(fix|hotfix)(\(.+\))?:/i', $subject) || preg_match('/^(fix|fixed|patch)\b/i', $subject)) {
            return 'Fixed';
        }

        if (preg_match('/^(feat|feature)(\(.+\))?:/i', $subject)
            || preg_match('/^(add|adds|added|introduce|introduces|create|creates)\b/i', $subject)
        ) {
            return 'Added';
        }

        if (preg_match('/^(deprecate|deprecated)(\(.+\))?:/i', $subject) || preg_match('/^deprecat/i', $subject)) {
            return 'Deprecated';
        }

        if (0 !== preg_match('/^(remove|removed|delete|deleted|drop|dropped)\b/i', $subject)) {
            return 'Removed';
        }

        return 'Changed';
    }

    /**
     * Normalizes a commit subject by stripping conventional prefixes, tags, and extra whitespace, while preserving the core message.
     *
     * @param string $subject commit subject to normalize
     *
     * @return string normalized commit subject
     */
    public function normalize(string $subject): string
    {
        $subject = trim($subject);
        $subject = (string) preg_replace('/^\[[^\]]+\]\s*/', '', $subject);
        $subject = (string) preg_replace(
            '/^(feat|feature|fix|docs|doc|refactor|chore|ci|build|style|test|tests|perf)(\([^)]+\))?:\s*/i',
            '',
            $subject,
        );
        $subject = (string) preg_replace('/\s+/', ' ', $subject);

        if (! str_contains($subject, ' ') && preg_match('/^[a-z]/', $subject)) {
            return ucfirst($subject);
        }

        return 0 !== preg_match('/^[a-z]/', $subject) ? ucfirst($subject) : $subject;
    }
}
