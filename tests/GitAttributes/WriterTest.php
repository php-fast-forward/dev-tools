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

namespace FastForward\DevTools\Tests\GitAttributes;

use FastForward\DevTools\GitAttributes\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Writer::class)]
final class WriterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    private ObjectProphecy $filesystem;

    private Writer $writer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->writer = new Writer($this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillAppendTrailingLineFeed(): void
    {
        $this->writer->write('/project/.gitattributes', '*.zip -diff');

        $this->filesystem->dumpFile('/project/.gitattributes', "*.zip -diff\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillAlignAttributeColumnsUsingTheLongestPathSpec(): void
    {
        $this->writer->write(
            '/project/.gitattributes',
            implode("\n", ['* text=auto', '/.github/ export-ignore', '/.gitattributes export-ignore']),
        );

        $this->filesystem->dumpFile(
            '/project/.gitattributes',
            "*               text=auto\n"
            . "/.github/       export-ignore\n"
            . "/.gitattributes export-ignore\n",
        )
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillRespectEscapedWhitespaceInsideThePathSpec(): void
    {
        $this->writer->write(
            '/project/.gitattributes',
            implode("\n", ['docs\ with\ spaces export-ignore', '/.github/ export-ignore']),
        );

        $this->filesystem->dumpFile(
            '/project/.gitattributes',
            "docs\\ with\\ spaces export-ignore\n"
            . "/.github/          export-ignore\n",
        )
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillPreserveEmptyLines(): void
    {
        $this->writer->write('/project/.gitattributes', "/docs/ export-ignore\n\n/tests/ export-ignore");

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/  export-ignore\n\n/tests/ export-ignore\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillNormalizeMultipleSpaces(): void
    {
        $this->writer->write('/project/.gitattributes', '/docs/   export-ignore');

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/ export-ignore\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillPreserveComments(): void
    {
        $this->writer->write('/project/.gitattributes', "# Managed by dev-tools\n/docs/ export-ignore");

        $this->filesystem->dumpFile('/project/.gitattributes', "# Managed by dev-tools\n/docs/ export-ignore\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillHandleMultipleSpacesBetweenPathAndAttribute(): void
    {
        $this->writer->write('/project/.gitattributes', '/docs/    export-ignore');

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/ export-ignore\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillNormalizeContentWithExtraWhitespaceCharacters(): void
    {
        $this->writer->write('/project/.gitattributes', '  /docs/   export-ignore  ');

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/ export-ignore\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillHandlePathsWithoutAttributesAsRawEntry(): void
    {
        $this->writer->write('/project/.gitattributes', '/docs/');

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillHandlePathsWithOnlyWhitespaceAttributeAsRawEntry(): void
    {
        $this->writer->write('/project/.gitattributes', '/docs/  ');

        $this->filesystem->dumpFile('/project/.gitattributes', "/docs/\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillAlignWithTabSeparatedEntries(): void
    {
        $this->writer->write('/project/.gitattributes', "/short\tbinary\n/longer/path/to/file\tbinary");

        $this->filesystem->dumpFile(
            '/project/.gitattributes',
            "/short               binary\n/longer/path/to/file binary\n",
        )
            ->shouldBeCalledOnce();
    }
}
