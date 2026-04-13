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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\GitIgnoreCommand;
use FastForward\DevTools\Console\Command\SyncCommand;
use FastForward\DevTools\GitAttributes\CandidateProvider;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilter;
use FastForward\DevTools\GitAttributes\Merger as GitAttributesMerger;
use FastForward\DevTools\GitIgnore\Classifier;
use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\Merger as GitIgnoreMerger;
use FastForward\DevTools\GitIgnore\Reader;
use FastForward\DevTools\GitIgnore\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(SyncCommand::class)]
#[UsesClass(Reader::class)]
#[UsesClass(GitIgnore::class)]
#[UsesClass(Classifier::class)]
#[UsesClass(GitIgnoreMerger::class)]
#[UsesClass(Writer::class)]
#[UsesClass(GitIgnoreCommand::class)]
#[UsesClass(CandidateProvider::class)]
#[UsesClass(ExistenceChecker::class)]
#[UsesClass(ExportIgnoreFilter::class)]
#[UsesClass(GitAttributesMerger::class)]
final class SyncCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return SyncCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'dev-tools:sync';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, .editorconfig, and .gitattributes in the root project.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command adds or updates dev-tools scripts in composer.json, copies reusable GitHub Actions workflows, ensures .editorconfig is present and up to date, and manages .gitattributes export-ignore rules.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteInfo(): void
    {
        $this->filesystem->exists(Argument::any())->willReturn(true);
        $this->filesystem->dumpFile(Argument::cetera())->shouldBeCalled();

        $this->output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        self::assertSame(SyncCommand::SUCCESS, $this->invokeExecute());
    }
}
