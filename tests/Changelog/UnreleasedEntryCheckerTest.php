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

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\GitProcessRunnerInterface;
use FastForward\DevTools\Changelog\UnreleasedEntryChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function Safe\file_put_contents;
use function Safe\mkdir;
use function uniqid;
use function sys_get_temp_dir;

#[CoversClass(UnreleasedEntryChecker::class)]
final class UnreleasedEntryCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GitProcessRunnerInterface>
     */
    private ObjectProphecy $gitProcessRunner;

    private string $workingDirectory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->gitProcessRunner = $this->prophesize(GitProcessRunnerInterface::class);
        $this->workingDirectory = sys_get_temp_dir() . '/' . uniqid('dev-tools-checker-', true);
        mkdir($this->workingDirectory);
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnTrueWhenUnreleasedSectionContainsEntries(): void
    {
        file_put_contents(
            $this->workingDirectory . '/CHANGELOG.md',
            $this->createChangelog('- Added changelog automation.')
        );

        self::assertTrue(
            (new UnreleasedEntryChecker($this->gitProcessRunner->reveal()))->hasPendingChanges($this->workingDirectory),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillCompareAgainstBaselineReference(): void
    {
        file_put_contents(
            $this->workingDirectory . '/CHANGELOG.md',
            $this->createChangelog('- Added changelog automation.')
        );
        $this->gitProcessRunner->run(['git', 'show', 'origin/main:CHANGELOG.md'], $this->workingDirectory)
            ->willReturn($this->createChangelog('- Added changelog automation.'));

        self::assertFalse(
            (new UnreleasedEntryChecker($this->gitProcessRunner->reveal()))
                ->hasPendingChanges($this->workingDirectory, 'origin/main'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnTrueWhenBaselineDoesNotContainNewEntries(): void
    {
        file_put_contents(
            $this->workingDirectory . '/CHANGELOG.md',
            $this->createChangelog('- Added changelog automation.')
        );
        $this->gitProcessRunner->run(['git', 'show', 'origin/main:CHANGELOG.md'], $this->workingDirectory)
            ->willReturn($this->createChangelog('- Nothing.'));

        self::assertTrue(
            (new UnreleasedEntryChecker($this->gitProcessRunner->reveal()))
                ->hasPendingChanges($this->workingDirectory, 'origin/main'),
        );
    }

    /**
     * @param string $entry
     *
     * @return string
     */
    private function createChangelog(string $entry): string
    {
        return "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),\nand this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).\n\n## [Unreleased]\n\n### Added\n\n{$entry}\n\n## [1.0.0] - 2026-04-08\n\n### Added\n\n- Initial release.\n\n[unreleased]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.0...HEAD\n[1.0.0]: https://github.com/php-fast-forward/dev-tools/releases/tag/v1.0.0\n";
    }
}
