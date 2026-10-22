<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\GitIgnore;

/**
 * Defines the contract for merging .gitignore entries.
 *
 * This service SHALL combine canonical and project-specific .gitignore
 * definitions into an ordered result. Canonical defaults MUST precede consumer
 * rules. The merger MUST preserve pattern whitespace and repeated rules because
 * Git resolves conflicts using the last matching pattern.
 */
interface MergerInterface
{
    /**
     * Merges two GitIgnore instances without changing consumer rule precedence.
     *
     * An already present canonical prefix MUST NOT be added again.
     * The path from $project is used in the returned instance.
     *
     * @param GitIgnoreInterface $canonical the canonical .gitignore from dev-tools
     * @param GitIgnoreInterface $project the project-specific .gitignore
     *
     * @return GitIgnoreInterface a new GitIgnore instance with merged entries
     */
    public function merge(GitIgnoreInterface $canonical, GitIgnoreInterface $project): GitIgnoreInterface;
}
