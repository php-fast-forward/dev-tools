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

use FastForward\DevTools\Changelog\Bootstrapper;
use FastForward\DevTools\Changelog\BootstrapResult;
use FastForward\DevTools\Changelog\HistoryGeneratorInterface;
use FastForward\DevTools\Changelog\KeepAChangelogConfigRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function Safe\file_get_contents;
use function Safe\mkdir;
use function uniqid;
use function sys_get_temp_dir;

#[CoversClass(Bootstrapper::class)]
#[UsesClass(BootstrapResult::class)]
#[UsesClass(KeepAChangelogConfigRenderer::class)]
final class BootstrapperTest extends TestCase
{
    private Filesystem $filesystem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillCreateMissingConfigAndChangelogFiles(): void
    {
        $workingDirectory = $this->createTemporaryDirectory();
        $historyGenerator = new class implements HistoryGeneratorInterface {
            /**
             * @param string $workingDirectory
             *
             * @return string
             */
            public function generate(string $workingDirectory): string
            {
                return "# Changelog\n\nAll notable changes to this project will be documented in this file, in reverse chronological order by release.\n\n## Unreleased - TBD\n\n### Added\n\n- Nothing.\n";
            }
        };

        $result = new Bootstrapper($this->filesystem, $historyGenerator)
            ->bootstrap($workingDirectory);

        self::assertTrue($result->configCreated);
        self::assertTrue($result->changelogCreated);
        self::assertFalse($result->unreleasedCreated);
        self::assertFileExists($workingDirectory . '/.keep-a-changelog.ini');
        self::assertFileExists($workingDirectory . '/CHANGELOG.md');
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillRestoreMissingUnreleasedSection(): void
    {
        $workingDirectory = $this->createTemporaryDirectory();

        $this->filesystem->dumpFile(
            $workingDirectory . '/CHANGELOG.md',
            "# Changelog\n\nAll notable changes to this project will be documented in this file, in reverse chronological order by release.\n\n## 1.0.0 - 2026-04-08\n\n### Added\n\n- Initial release.\n",
        );
        $this->filesystem->dumpFile($workingDirectory . '/.keep-a-changelog.ini', "[defaults]\n");

        $result = new Bootstrapper($this->filesystem)
            ->bootstrap($workingDirectory);

        self::assertFalse($result->configCreated);
        self::assertFalse($result->changelogCreated);
        self::assertTrue($result->unreleasedCreated);
        self::assertStringContainsString('## Unreleased - TBD', file_get_contents($workingDirectory . '/CHANGELOG.md'));
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillRestoreMissingUnreleasedSectionForExistingCustomIntro(): void
    {
        $workingDirectory = $this->createTemporaryDirectory();

        $this->filesystem->dumpFile(
            $workingDirectory . '/CHANGELOG.md',
            "# Changelog\n\nProject-specific introduction.\n\n## 1.0.0 - 2026-04-08\n\n### Added\n\n- Initial release.\n",
        );
        $this->filesystem->dumpFile($workingDirectory . '/.keep-a-changelog.ini', "[defaults]\n");

        $result = new Bootstrapper($this->filesystem)
            ->bootstrap($workingDirectory);

        self::assertFalse($result->configCreated);
        self::assertFalse($result->changelogCreated);
        self::assertTrue($result->unreleasedCreated);
        self::assertStringContainsString(
            "Project-specific introduction.\n\n## Unreleased - TBD\n\n### Added",
            file_get_contents($workingDirectory . '/CHANGELOG.md'),
        );
    }

    /**
     * @return string
     */
    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/' . uniqid('dev-tools-changelog-', true);
        mkdir($directory);

        return $directory;
    }
}
