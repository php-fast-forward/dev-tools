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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogEntryCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogEntryCommand::class)]
#[UsesClass(ChangelogEntryType::class)]
#[UsesTrait(LogsCommandResults::class)]
final class ChangelogEntryCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $changelogManager;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ChangelogEntryCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('type')
            ->willReturn('added');
        $this->input->getOption('release')
            ->willReturn('Unreleased');
        $this->input->getOption('date')
            ->willReturn(null);
        $this->input->getArgument('message')
            ->willReturn('Document the new workflow');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogEntryCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillAddAnUnreleasedEntry(): void
    {
        $this->changelogManager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Added,
            'Document the new workflow',
            'Unreleased',
            null,
        )->shouldBeCalled();
        $this->logger->log(
            'info',
            'Added {type} changelog entry to [{release}] in {absolute_file}.',
            [
                'input' => $this->input->reveal(),
                'absolute_file' => '/repo/CHANGELOG.md',
                'type' => 'added',
                'release' => 'Unreleased',
                'date' => null,
                'message' => 'Document the new workflow',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPassPublishedReleaseMetadata(): void
    {
        $this->input->getOption('type')
            ->willReturn('fixed');
        $this->input->getOption('release')
            ->willReturn('1.2.0');
        $this->input->getOption('date')
            ->willReturn('2026-04-21');
        $this->input->getArgument('message')
            ->willReturn('Fix changelog export order');
        $this->changelogManager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Fixed,
            'Fix changelog export order',
            '1.2.0',
            '2026-04-21',
        )->shouldBeCalled();
        $this->logger->log(
            'info',
            'Added {type} changelog entry to [{release}] in {absolute_file}.',
            [
                'input' => $this->input->reveal(),
                'absolute_file' => '/repo/CHANGELOG.md',
                'type' => 'fixed',
                'release' => '1.2.0',
                'date' => '2026-04-21',
                'message' => 'Fix changelog export order',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
