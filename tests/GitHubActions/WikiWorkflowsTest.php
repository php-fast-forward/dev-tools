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

namespace FastForward\DevTools\Tests\GitHubActions;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversNothing]
final class WikiWorkflowsTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function wikiPreviewWorkflowWillSkipWikiOperationsWhenNoWikiRepositoryExists(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/wiki-preview.yml');
        $steps = $workflow['jobs']['preview']['steps'] ?? null;

        self::assertIsArray($steps);
        self::assertSame('detect_wiki', $this->findStep($steps, 'Detect wiki repository')['id'] ?? null);
        self::assertSame(
            "steps.detect_wiki.outputs.exists == 'true'",
            $this->findStep($steps, 'Setup PHP and install dependencies')['if'] ?? null,
        );
        self::assertSame(
            "steps.detect_wiki.outputs.exists == 'true'",
            $this->findStep($steps, 'Refresh wiki preview pointer')['if'] ?? null,
        );
        self::assertStringContainsString('Wiki repository detected', $this->findSummaryMarkdown($steps) ?? '');
    }

    /**
     * @return void
     */
    #[Test]
    public function wikiMaintenanceWorkflowWillSkipWikiOperationsWhenNoWikiRepositoryExists(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/wiki-maintenance.yml');

        self::assertIsArray($workflow['jobs']['publish']['steps'] ?? null);
        self::assertSame(
            "steps.detect_wiki.outputs.exists == 'true'",
            $this->findStep(
                $workflow['jobs']['publish']['steps'],
                'Prepare wiki publish branch from preview branch'
            )['if'] ?? null,
        );
        self::assertSame(
            "steps.detect_wiki.outputs.exists == 'true'",
            $this->findStep(
                $workflow['jobs']['cleanup_closed_preview']['steps'],
                'Delete wiki preview branch'
            )['if'] ?? null,
        );
        self::assertSame(
            "steps.detect_wiki.outputs.exists == 'true'",
            $this->findStep(
                $workflow['jobs']['cleanup_orphaned_previews']['steps'],
                'Delete wiki branches for closed pull requests'
            )['if'] ?? null,
        );
        self::assertStringContainsString(
            'Wiki repository detected',
            $this->findSummaryMarkdown($workflow['jobs']['publish']['steps']) ?? '',
        );
    }

    /**
     * @param array<int, mixed> $steps
     * @param string $name
     *
     * @return array<string, mixed>
     */
    private function findStep(array $steps, string $name): array
    {
        foreach ($steps as $step) {
            if (! \is_array($step)) {
                continue;
            }

            if (($step['name'] ?? null) !== $name) {
                continue;
            }

            return $step;
        }

        self::fail(\sprintf('Step "%s" was not found.', $name));
    }

    /**
     * @param array<int, mixed> $steps
     */
    private function findSummaryMarkdown(array $steps): ?string
    {
        foreach ($steps as $step) {
            if (! \is_array($step)) {
                continue;
            }

            if (($step['uses'] ?? null) !== './.dev-tools-actions/.github/actions/summary/write') {
                continue;
            }

            return $step['with']['markdown'] ?? null;
        }

        return null;
    }
}
