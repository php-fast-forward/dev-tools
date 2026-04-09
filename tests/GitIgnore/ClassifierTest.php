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

namespace FastForward\DevTools\Tests\GitIgnore;

use FastForward\DevTools\GitIgnore\Classifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Classifier::class)]
final class ClassifierTest extends TestCase
{
    private Classifier $classifier;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->classifier = new Classifier();
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithTrailingSlashReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('vendor/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithWildcardSlashReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('logs/*/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithStarSlashStarReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('*/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithDoubleStarSlashReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('**/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithDoubleStarSlashAtStartReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('**/logs'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithStarSlashStarMidPatternReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('*/logs'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithWildcardMidPatternReturnsDirectory(): void
    {
        self::assertSame('directory', $this->classifier->classify('logs/*/cache'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithFilePatternReturnsFile(): void
    {
        self::assertSame('file', $this->classifier->classify('*.log'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithCommentReturnsFile(): void
    {
        self::assertSame('file', $this->classifier->classify('# comment'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithEmptyStringReturnsFile(): void
    {
        self::assertSame('file', $this->classifier->classify(''));
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWithSpecificFileReturnsFile(): void
    {
        self::assertSame('file', $this->classifier->classify('composer.json'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isDirectoryWithTrailingSlashReturnsTrue(): void
    {
        self::assertTrue($this->classifier->isDirectory('logs/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isDirectoryWithFilePatternReturnsFalse(): void
    {
        self::assertFalse($this->classifier->isDirectory('*.log'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isFileWithFilePatternReturnsTrue(): void
    {
        self::assertTrue($this->classifier->isFile('*.log'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isFileWithDirectoryPatternReturnsFalse(): void
    {
        self::assertFalse($this->classifier->isFile('vendor/'));
    }
}
