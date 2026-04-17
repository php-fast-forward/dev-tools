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

namespace FastForward\DevTools\Tests\GitIgnore;

use FastForward\DevTools\GitIgnore\ClassifierInterface;
use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\GitIgnoreInterface;
use FastForward\DevTools\GitIgnore\Merger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(Merger::class)]
#[UsesClass(GitIgnore::class)]
final class MergerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ClassifierInterface>
     */
    private ObjectProphecy $classifier;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreDevTools;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreProject;

    private Merger $merger;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->classifier = $this->prophesize(ClassifierInterface::class);
        $this->gitIgnoreDevTools = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreProject = $this->prophesize(GitIgnoreInterface::class);

        $this->gitIgnoreDevTools->path()
            ->willReturn('/canonical/.gitignore');
        $this->gitIgnoreProject->path()
            ->willReturn('/project/.gitignore');

        $this->merger = new Merger($this->classifier->reveal());
    }

    /**
     * @param string $entries
     *
     * @return self
     */
    private function withDirectoryClassification(string ...$entries): self
    {
        foreach ($entries as $entry) {
            $this->classifier->isDirectory($entry)
                ->willReturn(true);
        }

        return $this;
    }

    /**
     * @param string $entries
     *
     * @return self
     */
    private function withFileClassification(string ...$entries): self
    {
        foreach ($entries as $entry) {
            $this->classifier->isDirectory($entry)
                ->willReturn(false);
        }

        return $this;
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWithEmptyEntries(): void
    {
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn([]);
        $this->gitIgnoreProject->entries()
            ->willReturn(['*.log']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertSame(['*.log'], $result->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeRemovesDuplicates(): void
    {
        $this->withDirectoryClassification('vendor/', 'node_modules/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['vendor/', '*.log']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['vendor/', 'node_modules/']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertCount(3, $result->entries());
        self::assertContains('vendor/', $result->entries());
        self::assertContains('*.log', $result->entries());
        self::assertContains('node_modules/', $result->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeSortsDirectoriesFirst(): void
    {
        $this->withDirectoryClassification('vendor/', 'node_modules/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['*.log']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['vendor/', 'node_modules/']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());
        $entries = $result->entries();

        self::assertCount(3, $entries);
        self::assertContains('vendor/', $entries);
        self::assertContains('node_modules/', $entries);
        self::assertContains('*.log', $entries);

        self::assertTrue($this->isBeforeInArray('vendor/', $entries, '*.log'));
        self::assertTrue($this->isBeforeInArray('node_modules/', $entries, '*.log'));
    }

    /**
     * @param string $needle
     * @param array $haystack
     * @param string $before
     *
     * @return bool
     */
    private function isBeforeInArray(string $needle, array $haystack, string $before): bool
    {
        return array_search($needle, $haystack, true) < array_search($before, $haystack, true);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeRemovesComments(): void
    {
        $this->withDirectoryClassification('vendor/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['# comment', 'vendor/']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['# another comment', '*.log']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertNotContains('# comment', $result->entries());
        self::assertNotContains('# another comment', $result->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeRemovesEmptyEntries(): void
    {
        $this->withDirectoryClassification('vendor/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['', '  ', 'vendor/']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['*.log', '', '   ']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertNotContains('', $result->entries());
        self::assertNotContains('  ', $result->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeUsesProjectPath(): void
    {
        $this->withDirectoryClassification('vendor/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['vendor/']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['*.log']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertSame('/project/.gitignore', $result->path());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeReturnsGitIgnoreInstance(): void
    {
        $this->withDirectoryClassification('vendor/');
        $this->withFileClassification('*.log');

        $this->gitIgnoreDevTools->entries()
            ->willReturn(['vendor/']);
        $this->gitIgnoreProject->entries()
            ->willReturn(['*.log']);

        $result = $this->merger->merge($this->gitIgnoreDevTools->reveal(), $this->gitIgnoreProject->reveal());

        self::assertInstanceOf(GitIgnore::class, $result);
    }
}
