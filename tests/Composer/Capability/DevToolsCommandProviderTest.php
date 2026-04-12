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

namespace FastForward\DevTools\Tests\Composer\Capability;

use FastForward\DevTools\Command\AbstractCommand;
use FastForward\DevTools\Command\ChangelogCheckCommand;
use FastForward\DevTools\Command\ChangelogInitCommand;
use FastForward\DevTools\Command\CodeStyleCommand;
use FastForward\DevTools\Command\CopyLicenseCommand;
use FastForward\DevTools\Command\DependenciesCommand;
use FastForward\DevTools\Command\DocsCommand;
use FastForward\DevTools\Command\GitAttributesCommand;
use FastForward\DevTools\Command\GitIgnoreCommand;
use FastForward\DevTools\Command\SyncCommand;
use FastForward\DevTools\Command\SkillsCommand;
use FastForward\DevTools\Agent\Skills\SkillsSynchronizer;
use FastForward\DevTools\Command\PhpDocCommand;
use FastForward\DevTools\Command\RefactorCommand;
use FastForward\DevTools\Command\ReportsCommand;
use FastForward\DevTools\Command\StandardsCommand;
use FastForward\DevTools\Command\TestsCommand;
use FastForward\DevTools\Command\WikiCommand;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\GitAttributes\CandidateProvider;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilter;
use FastForward\DevTools\GitAttributes\Merger as GitAttributesMerger;
use FastForward\DevTools\GitAttributes\Reader as GitAttributesReader;
use FastForward\DevTools\GitAttributes\Writer as GitAttributesWriter;
use FastForward\DevTools\GitIgnore\Merger as GitIgnoreMerger;
use FastForward\DevTools\GitIgnore\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DevToolsCommandProvider::class)]
#[UsesClass(ChangelogInitCommand::class)]
#[UsesClass(ChangelogCheckCommand::class)]
#[UsesClass(CodeStyleCommand::class)]
#[UsesClass(RefactorCommand::class)]
#[UsesClass(TestsCommand::class)]
#[UsesClass(DependenciesCommand::class)]
#[UsesClass(PhpDocCommand::class)]
#[UsesClass(DocsCommand::class)]
#[UsesClass(StandardsCommand::class)]
#[UsesClass(ReportsCommand::class)]
#[UsesClass(WikiCommand::class)]
#[UsesClass(SyncCommand::class)]
#[UsesClass(GitIgnoreCommand::class)]
#[UsesClass(GitAttributesCommand::class)]
#[UsesClass(SkillsCommand::class)]
#[UsesClass(CopyLicenseCommand::class)]
#[UsesClass(SkillsSynchronizer::class)]
#[UsesClass(CandidateProvider::class)]
#[UsesClass(ExistenceChecker::class)]
#[UsesClass(ExportIgnoreFilter::class)]
#[UsesClass(GitAttributesMerger::class)]
#[UsesClass(GitAttributesReader::class)]
#[UsesClass(GitAttributesWriter::class)]
#[UsesClass(GitIgnoreMerger::class)]
#[UsesClass(Writer::class)]
final class DevToolsCommandProviderTest extends TestCase
{
    private DevToolsCommandProvider $commandProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->commandProvider = new DevToolsCommandProvider();
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnAllSupportedCommandsInExpectedOrder(): void
    {
        self::assertEquals(
            [
                new CodeStyleCommand(),
                new RefactorCommand(),
                new TestsCommand(),
                new DependenciesCommand(),
                new ChangelogInitCommand(),
                new ChangelogCheckCommand(),
                new PhpDocCommand(),
                new DocsCommand(),
                new StandardsCommand(),
                new ReportsCommand(),
                new WikiCommand(),
                new SyncCommand(),
                new GitIgnoreCommand(),
                new GitAttributesCommand(),
                new SkillsCommand(),
                new CopyLicenseCommand(),
            ],
            $this->commandProvider->getCommands(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnOnlyAbstractCommandImplementations(): void
    {
        foreach ($this->commandProvider->getCommands() as $command) {
            self::assertInstanceOf(AbstractCommand::class, $command);
        }
    }
}
