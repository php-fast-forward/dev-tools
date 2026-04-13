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

namespace FastForward\DevTools\Tests\Command;

use FastForward\DevTools\Console\Command\GitIgnoreCommand;
use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\GitIgnoreInterface;
use FastForward\DevTools\GitIgnore\Merger;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\Reader;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\Writer;
use FastForward\DevTools\GitIgnore\WriterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test suite for the GitIgnoreCommand.
 *
 * This test class verifies that the GitIgnoreCommand correctly merges and
 * synchronizes .gitignore files using the Reader, Merger, and Writer components.
 */
#[CoversClass(GitIgnoreCommand::class)]
#[UsesClass(Reader::class)]
#[UsesClass(GitIgnore::class)]
#[UsesClass(Merger::class)]
#[UsesClass(Writer::class)]
final class GitIgnoreCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ReaderInterface>
     */
    private ObjectProphecy $reader;

    /**
     * @var ObjectProphecy<MergerInterface>
     */
    private ObjectProphecy $merger;

    /**
     * @var ObjectProphecy<WriterInterface>
     */
    private ObjectProphecy $writer;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreSource;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreMerge;

    /**
     * @var string The source .gitignore path.
     */
    private string $sourcePath;

    /**
     * @var string The target .gitignore path.
     */
    private string $targetPath;

    /**
     * Sets up the test fixtures.
     */
    protected function setUp(): void
    {
        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->merger = $this->prophesize(MergerInterface::class);
        $this->writer = $this->prophesize(WriterInterface::class);
        $this->gitIgnoreSource = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreMerge = $this->prophesize(GitIgnoreInterface::class);

        $this->sourcePath = uniqid('source_', true) . '/.gitignore';
        $this->targetPath = uniqid('target_', true) . '/.gitignore';

        $this->reader->read($this->sourcePath)
            ->willReturn($this->gitIgnoreSource->reveal());
        $this->reader->read($this->targetPath)
            ->willReturn($this->gitIgnoreMerge->reveal());

        parent::setUp();
    }

    /**
     * Returns the command class under test.
     */
    protected function getCommandClass(): GitIgnoreCommand
    {
        return new GitIgnoreCommand(
            $this->merger->reveal(),
            $this->reader->reveal(),
            $this->writer->reveal(),
            $this->filesystem->reveal()
        );
    }

    /**
     * Returns the expected command name.
     */
    protected function getCommandName(): string
    {
        return 'gitignore';
    }

    /**
     * Returns the expected command description.
     */
    protected function getCommandDescription(): string
    {
        return 'Merges and synchronizes .gitignore files.';
    }

    /**
     * Returns the expected command help text.
     */
    protected function getCommandHelp(): string
    {
        return "This command merges the canonical .gitignore from dev-tools with the project's existing .gitignore.";
    }

    /**
     * Tests that execute() returns SUCCESS and correctly merges files.
     */
    #[Test]
    public function executeWillReturnSuccessAndMergeFiles(): void
    {
        $this->gitIgnoreSource->entries()
            ->willReturn(['# Canonical', 'vendor/', 'node_modules/']);
        $this->gitIgnoreMerge->entries()
            ->willReturn(['# Project', '*.log', 'tmp/']);

        $this->merger->merge($this->gitIgnoreSource->reveal(), $this->gitIgnoreMerge->reveal())
            ->willReturn(new GitIgnore($this->targetPath, ['vendor/', 'node_modules/', '*.log', 'tmp/']));

        $this->writer->write(Argument::that(
            static fn($gitIgnore): bool => $gitIgnore instanceof GitIgnore && $gitIgnore->entries() === [
                'vendor/',
                'node_modules/',
                '*.log',
                'tmp/',
            ]
        ))->shouldBeCalled();

        $this->output->writeln('<info>Merging .gitignore files...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>Successfully merged .gitignore file.</info>')
            ->shouldBeCalled();

        $this->input->getOption('source')
            ->willReturn($this->sourcePath);
        $this->input->getOption('target')
            ->willReturn($this->targetPath);

        self::assertSame(GitIgnoreCommand::SUCCESS, $this->invokeExecute());
    }
}
