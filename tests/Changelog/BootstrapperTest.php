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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Bootstrapper::class)]
#[UsesClass(BootstrapResult::class)]
#[UsesClass(KeepAChangelogConfigRenderer::class)]
final class BootstrapperTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $historyGenerator;

    private ObjectProphecy $configRenderer;

    private string $workingDirectory;

    private Bootstrapper $bootstrapper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->historyGenerator = $this->prophesize(HistoryGeneratorInterface::class);
        $this->configRenderer = $this->prophesize(KeepAChangelogConfigRenderer::class);
        $this->workingDirectory = '/tmp/fake-dir';

        $this->bootstrapper = new Bootstrapper(
            $this->filesystem->reveal(),
            $this->historyGenerator->reveal(),
            $this->configRenderer->reveal()
        );
    }

    /**
     * @return void
     */
    private function givenFilesExist(): void
    {
        $this->filesystem->exists('/tmp/fake-dir/.keep-a-changelog.ini')
            ->willReturn(true)
            ->shouldBeCalled();
        $this->filesystem->exists('/tmp/fake-dir/CHANGELOG.md')
            ->willReturn(true)
            ->shouldBeCalled();
    }

    /**
     * @return void
     */
    private function givenFilesDoNotExist(): void
    {
        $this->filesystem->exists('/tmp/fake-dir/.keep-a-changelog.ini')
            ->willReturn(false)
            ->shouldBeCalled();
        $this->filesystem->exists('/tmp/fake-dir/CHANGELOG.md')
            ->willReturn(false)
            ->shouldBeCalled();
        $this->configRenderer->render()
            ->willReturn('[defaults]')
            ->shouldBeCalled();
        $this->filesystem->dumpFile('/tmp/fake-dir/.keep-a-changelog.ini', '[defaults]')
            ->shouldBeCalled();
        $this->historyGenerator->generate('/tmp/fake-dir')
            ->willReturn('# Changelog')
            ->shouldBeCalled();
        $this->filesystem->dumpFile('/tmp/fake-dir/CHANGELOG.md', '# Changelog')
            ->shouldBeCalled();
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillCreateMissingConfigAndChangelogFiles(): void
    {
        $this->givenFilesDoNotExist();

        $result = $this->bootstrapper->bootstrap($this->workingDirectory);

        self::assertTrue($result->configCreated);
        self::assertTrue($result->changelogCreated);
        self::assertFalse($result->unreleasedCreated);
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillRestoreMissingUnreleasedSection(): void
    {
        $this->givenFilesExist();
        $this->filesystem->readFile('/tmp/fake-dir/CHANGELOG.md')
            ->willReturn(
                "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),\nand this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).\n\n## [1.0.0] - 2026-04-08\n\n### Added\n\n- Initial release.\n"
            )
            ->shouldBeCalled();
        $this->filesystem->dumpFile(
            '/tmp/fake-dir/CHANGELOG.md',
            Argument::that(fn(string $content): bool => str_contains($content, '## [Unreleased]'))
        )->shouldBeCalled();

        $result = $this->bootstrapper->bootstrap($this->workingDirectory);

        self::assertFalse($result->configCreated);
        self::assertFalse($result->changelogCreated);
        self::assertTrue($result->unreleasedCreated);
    }

    /**
     * @return void
     */
    #[Test]
    public function bootstrapWillRestoreMissingUnreleasedSectionForExistingCustomIntro(): void
    {
        $this->givenFilesExist();
        $this->filesystem->readFile('/tmp/fake-dir/CHANGELOG.md')
            ->willReturn(
                "# Changelog\n\nProject-specific introduction.\n\n## [1.0.0] - 2026-04-08\n\n### Added\n\n- Initial release.\n"
            )
            ->shouldBeCalled();
        $this->filesystem->dumpFile(
            '/tmp/fake-dir/CHANGELOG.md',
            Argument::that(
                fn(string $content): bool => str_contains(
                    $content,
                    "Project-specific introduction.\n\n## [Unreleased]\n\n## [1.0.0] - 2026-04-08"
                )
            )
        )->shouldBeCalled();

        $result = $this->bootstrapper->bootstrap($this->workingDirectory);

        self::assertFalse($result->configCreated);
        self::assertFalse($result->changelogCreated);
        self::assertTrue($result->unreleasedCreated);
    }
}
