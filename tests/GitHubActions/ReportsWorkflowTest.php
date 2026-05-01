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
final class ReportsWorkflowTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function reportsWorkflowWillDetectGeneratedReportSurfaces(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/reports.yml');
        $steps = $workflow['jobs']['reports']['steps'] ?? null;

        self::assertIsArray($steps);
        self::assertSame(
            'detect_reports',
            $this->findStep($steps, 'Detect generated report surfaces')['id'] ?? null,
        );
        self::assertSame(
            '${{ steps.detect_reports.outputs.docs_generated }}',
            $workflow['jobs']['reports']['outputs']['docs_generated'] ?? null,
        );
        self::assertSame(
            '${{ steps.detect_reports.outputs.coverage_generated }}',
            $workflow['jobs']['reports']['outputs']['coverage_generated'] ?? null,
        );
        self::assertSame(
            '${{ steps.detect_reports.outputs.metrics_generated }}',
            $workflow['jobs']['reports']['outputs']['metrics_generated'] ?? null,
        );
        self::assertSame(
            '${{ steps.detect_reports.outputs.any_generated }}',
            $workflow['jobs']['reports']['outputs']['any_generated'] ?? null,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function reportsWorkflowWillOnlyVerifyGeneratedArtifacts(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/reports.yml');
        $mainSteps = $workflow['jobs']['verify_main_reports']['steps'] ?? null;
        $previewSteps = $workflow['jobs']['verify_preview_reports']['steps'] ?? null;

        self::assertIsArray($mainSteps);
        self::assertIsArray($previewSteps);
        self::assertSame('build_checks', $this->findStep($mainSteps, 'Build deployment checks')['id'] ?? null);
        self::assertSame(
            "steps.build_checks.outputs.has_checks == 'true'",
            $this->findVerifyDeploymentStep($mainSteps)['if'] ?? null,
        );
        self::assertSame(
            '${{ steps.build_checks.outputs.checks }}',
            $this->findVerifyDeploymentStep($mainSteps)['with']['checks'] ?? null,
        );
        self::assertSame(
            '${{ steps.build_checks.outputs.checks }}',
            $this->findVerifyDeploymentStep($previewSteps)['with']['checks'] ?? null,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function reportsWorkflowWillOnlyCommentAndSummarizeGeneratedArtifacts(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/reports.yml');
        $commentSteps = $workflow['jobs']['comment_preview']['steps'] ?? null;
        $summarySteps = $workflow['jobs']['summarize']['steps'] ?? null;

        self::assertIsArray($commentSteps);
        self::assertIsArray($summarySteps);
        self::assertSame('build_comment', $this->findStep($commentSteps, 'Build preview comment')['id'] ?? null);
        self::assertSame(
            "steps.build_comment.outputs.has_links == 'true'",
            $this->findStep($commentSteps, 'Comment preview URLs on pull request')['if'] ?? null,
        );
        self::assertSame(
            '${{ steps.build_comment.outputs.message }}',
            $this->findStep($commentSteps, 'Comment preview URLs on pull request')['with']['message'] ?? null,
        );
        self::assertStringContainsString(
            'if [ "${DOCS_GENERATED}" = \'true\' ]; then',
            (string) ($this->findStep($summarySteps, null, 'build_summary')['run'] ?? ''),
        );
        self::assertStringContainsString(
            'if [ "${COVERAGE_GENERATED}" = \'true\' ]; then',
            (string) ($this->findStep($summarySteps, null, 'build_summary')['run'] ?? ''),
        );
        self::assertStringContainsString(
            'if [ "${METRICS_GENERATED}" = \'true\' ]; then',
            (string) ($this->findStep($summarySteps, null, 'build_summary')['run'] ?? ''),
        );
    }

    /**
     * @param array<int, mixed> $steps
     * @param string|null $name
     * @param string|null $id
     *
     * @return array<string, mixed>
     */
    private function findStep(array $steps, ?string $name = null, ?string $id = null): array
    {
        foreach ($steps as $step) {
            if (! \is_array($step)) {
                continue;
            }

            if (null !== $name && ($step['name'] ?? null) !== $name) {
                continue;
            }

            if (null !== $id && ($step['id'] ?? null) !== $id) {
                continue;
            }

            return $step;
        }

        self::fail('Requested workflow step was not found.');
    }

    /**
     * @param array<int, mixed> $steps
     *
     * @return array<string, mixed>
     */
    private function findVerifyDeploymentStep(array $steps): array
    {
        foreach ($steps as $step) {
            if (! \is_array($step)) {
                continue;
            }

            if (($step['uses'] ?? null) !== './.dev-tools-actions/.github/actions/github-pages/verify-deployment') {
                continue;
            }

            return $step;
        }

        self::fail('Verify deployment step was not found.');
    }
}
