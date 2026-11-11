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
 * Merges ordered .gitignore rules with canonical defaults before consumer overrides.
 *
 * Existing patterns MUST retain their order, whitespace and repeated occurrences.
 */
final readonly class Merger implements MergerInterface
{
    /**
     * Retains the classifier constructor parameter for compatibility with consumers.
     *
     * @param ClassifierInterface $classifier
     */
    public function __construct(
        private ClassifierInterface $classifier = new Classifier()
    ) {}

    /**
     * Prepends canonical defaults once, allowing later consumer rules to override them.
     *
     * @param GitIgnoreInterface $canonical the canonical baseline
     * @param GitIgnoreInterface $project the consumer rules and destination path
     */
    public function merge(GitIgnoreInterface $canonical, GitIgnoreInterface $project): GitIgnoreInterface
    {
        $canonicalEntries = $this->patterns($canonical->entries());
        $projectEntries = $this->patterns($project->entries());

        if (\array_slice($projectEntries, 0, \count($canonicalEntries)) === $canonicalEntries) {
            return new GitIgnore($project->path(), $projectEntries);
        }

        return new GitIgnore($project->path(), [...$canonicalEntries, ...$projectEntries]);
    }

    /**
     * Removes only blank lines and comments, never normalizing pattern whitespace.
     *
     * @param list<string> $entries the raw rules
     *
     * @return list<string>
     */
    private function patterns(array $entries): array
    {
        return array_values(array_filter(
            $entries,
            static fn(string $entry): bool => '' !== trim($entry) && ! str_starts_with($entry, '#'),
        ));
    }
}
