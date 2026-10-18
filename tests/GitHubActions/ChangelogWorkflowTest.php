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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversNothing]
final class ChangelogWorkflowTest extends TestCase
{
    /**
     * @param string $path
     *
     * @return void
     */
    #[Test]
    #[TestWith(['.github/workflows/changelog.yml'])]
    #[TestWith(['resources/github-actions/changelog.yml'])]
    public function workflowWillDelegateToTheExternalLibraryWithoutPublishing(string $path): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../' . $path);
        $job = $workflow['jobs']['changelog'];

        self::assertSame('php-fast-forward/changelog/.github/workflows/changelog.yml@v0', $job['uses']);
        self::assertSame([], $workflow['permissions']);
        self::assertTrue($job['with']['resolve-pull-requests']);
        self::assertTrue($job['with']['prepare-release']);
        self::assertFalse($job['with']['publish']);
        self::assertSame('main', $job['with']['base-branch']);
        self::assertSame('tags', $job['with']['source']);
        self::assertSame('Prepare release v{next_version}', $job['with']['version-pr-title']);
        self::assertSame([
            'contents' => 'write',
            'pull-requests' => 'write',
            'issues' => 'read',
            'statuses' => 'write',
        ], $job['permissions']);
        self::assertContains('closed', $workflow['on']['pull_request_target']['types']);
        self::assertSame(['main'], $workflow['on']['push']['branches']);
    }

    /**
     * @return void
     */
    #[Test]
    public function compatibilityCallerWillPreserveRunnerTokenPathsAndReleaseBranchInputs(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/changelog.yml');
        $job = $workflow['jobs']['changelog'];

        self::assertSame('${{ inputs.runs-on || \'["ubuntu-latest"]\' }}', $job['with']['runs-on']);
        self::assertSame('${{ secrets.token }}', $job['secrets']['token']);
        self::assertSame('${{ inputs.changelog-file || \'CHANGELOG.md\' }}', $job['with']['changelog-file']);
        self::assertSame(
            '${{ format(\'{0}next\', inputs.release-branch-prefix || \'release/v\') }}',
            $job['with']['managed-branch']
        );
        self::assertSame('.changelog', $job['with']['fragment-directory']);
        self::assertSame('en', $job['with']['locale']);
        self::assertSame('v', $job['with']['tag-prefix']);
        self::assertSame('compatibility', $job['needs']);
        self::assertSame(
            '${{ inputs.version }}',
            $workflow['jobs']['compatibility']['steps'][0]['env']['LEGACY_VERSION']
        );
        self::assertStringContainsString('exit 1', $workflow['jobs']['compatibility']['steps'][0]['run']);
        self::assertSame('Release Prepared', $workflow['jobs']['project_prepared']['steps'][1]['with']['to-status']);
    }

    /**
     * @return void
     */
    #[Test]
    public function versionChecksWillVerifyTheLiveBotBranchAndHeadBeforeDispatching(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/changelog.yml');
        $job = $workflow['jobs']['version_checks'];
        $step = $job['steps'][0];
        $script = $step['with']['script'];

        self::assertSame('changelog', $job['needs']);
        self::assertSame('write', $job['permissions']['actions']);
        self::assertSame('read', $job['permissions']['contents']);
        self::assertSame('read', $job['permissions']['pull-requests']);
        self::assertSame('actions/github-script@v8', $step['uses']);
        self::assertSame('${{ needs.changelog.outputs.version-head }}', $step['env']['VERSION_HEAD']);
        self::assertStringContainsString('github.rest.pulls.get', $script);
        self::assertStringContainsString("pr.state !== 'open'", $script);
        self::assertStringContainsString('pr.head.repo?.full_name !== context.payload.repository.full_name', $script);
        self::assertStringContainsString('pr.head.ref !== process.env.MANAGED_BRANCH', $script);
        self::assertStringContainsString('pr.head.sha !== process.env.VERSION_HEAD', $script);
        self::assertStringContainsString("pr.user.login !== 'github-actions[bot]'", $script);
        self::assertStringContainsString("workflow_id: 'tests.yml', ref: pr.head.ref", $script);
        self::assertStringContainsString("'publish-required-statuses': 'true'", $script);
    }
}
