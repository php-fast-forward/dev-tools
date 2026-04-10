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
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Writer::class)]
final class WriterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function writeWillAppendTrailingLineFeed(): void
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $writer = new Writer($filesystem->reveal());

        $writer->write('/project/.gitattributes', '*.zip -diff');

        $filesystem->dumpFile('/project/.gitattributes', "*.zip -diff\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWillAlignAttributeColumnsUsingTheLongestPathSpec(): void
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $writer = new Writer($filesystem->reveal());

        $writer->write(
            '/project/.gitattributes',
            implode("\n", ['* text=auto', '/.github/ export-ignore', '/.gitattributes export-ignore']),
        );

        $filesystem->dumpFile(
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
        $filesystem = $this->prophesize(Filesystem::class);
        $writer = new Writer($filesystem->reveal());

        $writer->write(
            '/project/.gitattributes',
            implode("\n", ['docs\ with\ spaces export-ignore', '/.github/ export-ignore']),
        );

        $filesystem->dumpFile(
            '/project/.gitattributes',
            "docs\\ with\\ spaces export-ignore\n"
            . "/.github/          export-ignore\n",
        )
            ->shouldBeCalledOnce();
    }
}
