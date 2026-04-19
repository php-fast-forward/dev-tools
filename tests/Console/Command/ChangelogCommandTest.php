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

use DateTimeImmutable;
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogEntryCommand;
use FastForward\DevTools\Console\Command\ChangelogNextVersionCommand;
use FastForward\DevTools\Console\Command\ChangelogPromoteCommand;
use FastForward\DevTools\Console\Command\ChangelogShowCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogEntryCommand::class)]
#[CoversClass(ChangelogPromoteCommand::class)]
#[CoversClass(ChangelogNextVersionCommand::class)]
#[CoversClass(ChangelogShowCommand::class)]
#[UsesClass(ChangelogEntryType::class)]
final class ChangelogCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChangelogManagerInterface>
     */
    private ObjectProphecy $manager;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    private ObjectProphecy $clock;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->manager = $this->prophesize(ChangelogManagerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->clock = $this->prophesize(ClockInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function entryCommandWillDelegateToTheManager(): void
    {
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('type')
            ->willReturn('added');
        $this->input->getOption('release')
            ->willReturn(ChangelogDocument::UNRELEASED_VERSION);
        $this->input->getOption('date')
            ->willReturn(null);
        $this->input->getArgument('message')
            ->willReturn('Add a release workflow');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md')
            ->shouldBeCalledOnce();
        $this->manager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Added,
            'Add a release workflow',
            ChangelogDocument::UNRELEASED_VERSION,
            null,
        )->shouldBeCalledOnce();

        $command = new ChangelogEntryCommand($this->filesystem->reveal(), $this->manager->reveal());

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->execute($command));
    }

    /**
     * @return void
     */
    #[Test]
    public function promoteCommandWillUseTheProvidedOrCurrentDate(): void
    {
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('date')
            ->willReturn(null);
        $this->input->getArgument('version')
            ->willReturn('1.2.0');
        $this->clock->now()
            ->willReturn(new DateTimeImmutable('2026-04-19T12:00:00+00:00'));
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md')
            ->shouldBeCalledOnce();
        $this->manager->promote('/repo/CHANGELOG.md', '1.2.0', '2026-04-19')
            ->shouldBeCalledOnce();

        $command = new ChangelogPromoteCommand(
            $this->filesystem->reveal(),
            $this->manager->reveal(),
            $this->clock->reveal(),
        );

        self::assertSame(ChangelogPromoteCommand::SUCCESS, $this->execute($command));
    }

    /**
     * @return void
     */
    #[Test]
    public function nextVersionCommandWillPrintTheInferredVersion(): void
    {
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('current-version')
            ->willReturn(null);
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md')
            ->shouldBeCalledOnce();
        $this->manager->inferNextVersion('/repo/CHANGELOG.md', null)
            ->willReturn('1.5.0')
            ->shouldBeCalledOnce();
        $this->output->writeln('1.5.0')
            ->shouldBeCalledOnce();

        $command = new ChangelogNextVersionCommand($this->filesystem->reveal(), $this->manager->reveal());

        self::assertSame(ChangelogNextVersionCommand::SUCCESS, $this->execute($command));
    }

    /**
     * @return void
     */
    #[Test]
    public function showCommandWillPrintTheRenderedReleaseNotes(): void
    {
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getArgument('version')
            ->willReturn('1.2.0');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md')
            ->shouldBeCalledOnce();
        $this->manager->renderReleaseNotes('/repo/CHANGELOG.md', '1.2.0')
            ->willReturn("### Added\n\n- Ship it\n")
            ->shouldBeCalledOnce();
        $this->output->write("### Added\n\n- Ship it\n")
            ->shouldBeCalledOnce();

        $command = new ChangelogShowCommand($this->filesystem->reveal(), $this->manager->reveal());

        self::assertSame(ChangelogShowCommand::SUCCESS, $this->execute($command));
    }

    /**
     * @param object $command
     *
     * @return int
     */
    private function execute(object $command): int
    {
        return (new ReflectionMethod($command, 'execute'))
            ->invoke($command, $this->input->reveal(), $this->output->reveal());
    }
}
