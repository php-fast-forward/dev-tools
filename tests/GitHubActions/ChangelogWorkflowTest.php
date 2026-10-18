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

use function Safe\file_get_contents;

#[CoversNothing]
final class ChangelogWorkflowTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function workflowWillUseTheOfficialSingleJobCallerWithAutomaticPublishing(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/changelog.yml');

        self::assertSame('Changelog', $workflow['name']);
        self::assertSame([
            'pull_request' => [
                'branches' => ['main'],
                'types' => ['opened', 'synchronize', 'reopened', 'ready_for_review', 'labeled', 'unlabeled'],
            ],
            'pull_request_target' => [
                'branches' => ['main'],
                'types' => ['opened', 'synchronize', 'reopened', 'ready_for_review', 'labeled', 'unlabeled', 'closed'],
            ],
            'push' => [
                'branches' => ['main'],
            ],
            'workflow_dispatch' => null,
        ], $workflow['on']);
        self::assertSame([], $workflow['permissions']);
        self::assertSame(['changelog'], array_keys($workflow['jobs']));
        self::assertSame([
            'permissions' => [
                'contents' => 'write',
                'pull-requests' => 'write',
                'issues' => 'read',
                'statuses' => 'write',
            ],
            'uses' => 'php-fast-forward/changelog/.github/workflows/changelog.yml@v0',
            'with' => [
                'base-branch' => 'main',
                'source' => 'tags',
                'comment-mode' => 'always',
                'prepare-release' => true,
                'publish' => true,
            ],
        ], $workflow['jobs']['changelog']);
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerTemplateWillMatchTheRepositoryCallerExactly(): void
    {
        self::assertSame(
            file_get_contents(__DIR__ . '/../../.github/workflows/changelog.yml'),
            file_get_contents(__DIR__ . '/../../resources/github-actions/changelog.yml'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function transientRetryWillMonitorTheCurrentChangelogWorkflowName(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/changelog.yml');
        $retryWorkflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/retry-transient-failures.yml');

        self::assertContains($workflow['name'], $retryWorkflow['on']['workflow_run']['workflows']);
    }
}
