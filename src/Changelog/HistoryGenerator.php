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

use Throwable;

use function array_values;

/**
 * Converts release metadata and commit subjects into a rendered changelog file.
 */
final readonly class HistoryGenerator implements HistoryGeneratorInterface
{
    /**
     * Initializes the `HistoryGenerator` with optional dependencies.
     *
     * @param GitReleaseCollectorInterface $gitReleaseCollector git release collector instance for collecting release metadata and commit subjects
     * @param CommitClassifierInterface $commitClassifier commit classifier instance for classifying and normalizing commit subjects into changelog sections
     * @param MarkdownRenderer $markdownRenderer markdown renderer instance for rendering the final changelog markdown from structured release and commit data
     * @param GitProcessRunnerInterface $gitProcessRunner git process runner used to resolve repository metadata required by the rendered changelog footer
     */
    public function __construct(
        private GitReleaseCollectorInterface $gitReleaseCollector = new GitReleaseCollector(),
        private CommitClassifierInterface $commitClassifier = new CommitClassifier(),
        private MarkdownRenderer $markdownRenderer = new MarkdownRenderer(),
        private GitProcessRunnerInterface $gitProcessRunner = new GitProcessRunner(),
    ) {}

    /**
     * @param string $workingDirectory
     *
     * @return string
     */
    public function generate(string $workingDirectory): string
    {
        $releases = [];
        $repositoryUrl = $this->resolveRepositoryUrl($workingDirectory);

        foreach ($this->gitReleaseCollector->collect($workingDirectory) as $release) {
            $entries = [];

            foreach ($release['commits'] as $subject) {
                $section = $this->commitClassifier->classify($subject);
                $entries[$section] ??= [];
                $entries[$section][] = $this->commitClassifier->normalize($subject);
            }

            foreach ($entries as $section => $sectionEntries) {
                $entries[$section] = array_values(array_unique($sectionEntries));
            }

            $releases[] = [
                'version' => $release['version'],
                'tag' => $release['tag'],
                'date' => $release['date'],
                'entries' => $entries,
            ];
        }

        return $this->markdownRenderer->render($releases, $repositoryUrl);
    }

    /**
     * @param string $workingDirectory
     *
     * @return string|null
     */
    private function resolveRepositoryUrl(string $workingDirectory): ?string
    {
        try {
            $repositoryUrl = $this->gitProcessRunner->run([
                'git',
                'config',
                '--get',
                'remote.origin.url',
            ], $workingDirectory);
        } catch (Throwable) {
            return null;
        }

        return '' === $repositoryUrl ? null : $repositoryUrl;
    }
}
