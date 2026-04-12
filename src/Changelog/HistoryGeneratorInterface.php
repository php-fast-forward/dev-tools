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
 * Builds initial changelog markdown from repository release history.
 *
 * The HistoryGeneratorInterface defines a contract for generating changelog markdown based on the release history of a repository.
 * Implementations of this interface MUST collect release metadata and commit subjects, classify and normalize commit subjects into changelog sections,
 * and render the final changelog markdown.
 */
interface HistoryGeneratorInterface
{
    /**
     * Generates changelog markdown from the release history of the repository in the given working directory.
     *
     * The generate method SHOULD collect release metadata and commit subjects using a GitReleaseCollectorInterface implementation,
     * classify and normalize commit subjects into changelog sections using a CommitClassifierInterface implementation,
     * and render the final changelog markdown using a MarkdownRenderer implementation. The method MUST return the generated changelog markdown as a string.
     *
     * @param string $workingDirectory Directory in which to generate the changelog (e.g., repository root).
     *
     * @return string Generated changelog markdown based on the repository's release history
     */
    public function generate(string $workingDirectory): string;
}
