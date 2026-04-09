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

use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Writer::class)]
#[UsesClass(GitIgnore::class)]
final class WriterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function writeDumpsContentToFile(): void
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $gitignore = new GitIgnore('/project/.gitignore', ['vendor/', '*.log']);

        $writer = new Writer($filesystem->reveal());
        $writer->write($gitignore);

        $filesystem->dumpFile('/project/.gitignore', "vendor/\n*.log\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWithEmptyEntriesDumpsEmptyString(): void
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $gitignore = new GitIgnore('/project/.gitignore', []);

        $writer = new Writer($filesystem->reveal());
        $writer->write($gitignore);

        $filesystem->dumpFile('/project/.gitignore', "\n")
            ->shouldBeCalledOnce();
    }

    /**
     * @return void
     */
    #[Test]
    public function writeWithMultipleEntriesJoinsWithNewline(): void
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $gitignore = new GitIgnore('/project/.gitignore', ['vendor/', 'node_modules/', '*.log']);

        $writer = new Writer($filesystem->reveal());
        $writer->write($gitignore);

        $filesystem->dumpFile(Argument::any(), "vendor/\nnode_modules/\n*.log\n")
            ->shouldBeCalledOnce();
    }
}
