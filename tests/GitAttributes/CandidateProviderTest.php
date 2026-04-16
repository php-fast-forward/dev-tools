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

namespace FastForward\DevTools\Tests\GitAttributes;

use FastForward\DevTools\GitAttributes\CandidateProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CandidateProvider::class)]
final class CandidateProviderTest extends TestCase
{
    private readonly CandidateProvider $provider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->provider = new CandidateProvider();
    }

    /**
     * @return void
     */
    #[Test]
    public function foldersWillReturnNonEmptyArray(): void
    {
        self::assertNotEmpty($this->provider->folders());
    }

    /**
     * @return void
     */
    #[Test]
    public function foldersWillStartWithSlash(): void
    {
        self::assertStringStartsWith('/', $this->provider->folders()[0]);
    }

    /**
     * @return void
     */
    #[Test]
    public function foldersWillEndWithSlash(): void
    {
        $folders = $this->provider->folders();

        self::assertStringEndsWith('/', $folders[0]);
    }

    /**
     * @return void
     */
    #[Test]
    public function filesWillReturnNonEmptyArray(): void
    {
        self::assertNotEmpty($this->provider->files());
    }

    /**
     * @return void
     */
    #[Test]
    public function filesWillStartWithSlash(): void
    {
        self::assertStringStartsWith('/', $this->provider->files()[0]);
    }

    /**
     * @return void
     */
    #[Test]
    public function filesWillNotEndWithSlash(): void
    {
        $files = $this->provider->files();

        self::assertStringEndsNotWith('/', $files[0]);
    }

    /**
     * @return void
     */
    #[Test]
    public function allWillCombineFoldersAndFiles(): void
    {
        self::assertCount(
            \count($this->provider->folders()) + \count($this->provider->files()),
            $this->provider->all(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function allWillHaveFoldersFirst(): void
    {
        $all = $this->provider->all();
        $foldersCount = \count($this->provider->folders());

        self::assertSame($this->provider->folders(), \array_slice($all, 0, $foldersCount));
    }

    /**
     * @return void
     */
    #[Test]
    public function allWillHaveFilesAfterFolders(): void
    {
        $all = $this->provider->all();

        $foldersCount = \count($this->provider->folders());

        self::assertSame($this->provider->files(), \array_slice($all, $foldersCount));
    }

    /**
     * @return void
     */
    #[Test]
    public function folderWillContainDotGithub(): void
    {
        self::assertContains('/.github/', $this->provider->folders());
    }

    /**
     * @return void
     */
    #[Test]
    public function filesWillContainGitignore(): void
    {
        self::assertContains('/.gitignore', $this->provider->files());
    }
}
