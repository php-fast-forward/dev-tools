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
final class ChangelogWorkflowTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function prepareReleasePullRequestWillKeepComposerPluginsEnabledForWikiPreviewRefresh(): void
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/changelog.yml');
        $steps = $workflow['jobs']['prepare_release_pull_request']['steps'] ?? null;

        self::assertIsArray($steps);

        $setupComposerStep = null;

        foreach ($steps as $step) {
            if (! \is_array($step)) {
                continue;
            }

            if (($step['name'] ?? null) !== 'Setup PHP and install dependencies') {
                continue;
            }

            if (($step['if'] ?? null) !== '${{ steps.create_pr.outputs.pull-request-number != \'\' }}') {
                continue;
            }

            $setupComposerStep = $step;

            break;
        }

        self::assertIsArray($setupComposerStep);
        self::assertSame(
            '--prefer-dist --no-progress --no-interaction --no-scripts',
            $setupComposerStep['with']['install-options'] ?? null,
        );
        self::assertStringNotContainsString(
            '--no-plugins',
            (string) ($setupComposerStep['with']['install-options'] ?? ''),
        );
    }
}
