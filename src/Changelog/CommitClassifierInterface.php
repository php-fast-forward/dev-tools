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
 * Maps raw commit subjects to Keep a Changelog sections.
 *
 * The CommitClassifierInterface defines a contract for classifying commit subjects into specific changelog
 * sections based on conventional prefixes and keywords.
 *
 * Implementations of this interface MUST analyze commit subjects and determine the appropriate
 * changelog section (e.g., "Added", "Changed", "Deprecated", "Removed", "Fixed", "Security", or "Uncategorized") based
 * on recognized patterns such as "fix:", "feat:", "docs:", "chore:", and security-related keywords.
 */
interface CommitClassifierInterface
{
    /**
     * Classifies a commit subject into a changelog section based on conventional prefixes and keywords.
     *
     * The classification logic SHOULD recognize common patterns such as "fix:", "feat:", "docs:", "chore:",
     * and security-related keywords, while also allowing for free-form subjects to be categorized under a default section.
     *
     * @param string $subject commit subject to classify
     *
     * @return string Changelog section name (e.g., "Added", "Changed", "Deprecated", "Removed", "Fixed", "Security", or "Uncategorized").
     */
    public function classify(string $subject): string;

    /**
     * Normalizes a commit subject by stripping conventional prefixes, tags, and extra whitespace, while preserving the core message.
     *
     * The normalization process SHOULD remove any conventional commit type indicators (e.g., "fix:", "feat:", "docs:")
     * and scope annotations (e.g., "(api)"),
     *
     * @param string $subject commit subject to normalize
     *
     * @return string normalized commit subject
     */
    public function normalize(string $subject): string;
}
