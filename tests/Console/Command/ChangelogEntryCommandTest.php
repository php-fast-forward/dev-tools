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

use InvalidArgumentException;
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogEntryCommand;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogEntryCommand::class)]
#[CoversClass(OutputFormat::class)]
#[UsesClass(ChangelogEntryType::class)]
final class ChangelogEntryCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChangelogManagerInterface>
     */
    private ObjectProphecy $changelogManager;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<CommandResponderFactoryInterface>
     */
    private ObjectProphecy $commandResponderFactory;

    /**
     * @var ObjectProphecy<CommandResponderInterface>
     */
    private ObjectProphecy $commandResponder;

    private ChangelogEntryCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);

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
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogEntryCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->commandResponderFactory->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillDelegateToTheManagerAndReturnSuccess(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Added,
            'Add a release workflow',
            ChangelogDocument::UNRELEASED_VERSION,
            null,
        )->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Added added changelog entry to [Unreleased] in /repo/CHANGELOG.md.',
            [
                'command' => 'changelog:entry',
                'file' => 'CHANGELOG.md',
                'type' => 'added',
                'release' => ChangelogDocument::UNRELEASED_VERSION,
                'date' => null,
                'message' => 'Add a release workflow',
            ],
        )->willReturn(ChangelogEntryCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillIncludeAnExplicitReleaseDateWhenProvided(): void
    {
        $this->input->getOption('type')
            ->willReturn('fixed');
        $this->input->getOption('release')
            ->willReturn('1.2.0');
        $this->input->getOption('date')
            ->willReturn('2026-04-20');
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Fixed,
            'Add a release workflow',
            '1.2.0',
            '2026-04-20',
        )->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Added fixed changelog entry to [1.2.0] in /repo/CHANGELOG.md.',
            [
                'command' => 'changelog:entry',
                'file' => 'CHANGELOG.md',
                'type' => 'fixed',
                'release' => '1.2.0',
                'date' => '2026-04-20',
                'message' => 'Add a release workflow',
            ],
        )->willReturn(ChangelogEntryCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenJsonOutputIsRequested(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->addEntry(
            '/repo/CHANGELOG.md',
            ChangelogEntryType::Added,
            'Add a release workflow',
            ChangelogDocument::UNRELEASED_VERSION,
            null,
        )->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Added added changelog entry to [Unreleased] in /repo/CHANGELOG.md.',
            Argument::type('array'),
        )->willReturn(ChangelogEntryCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogEntryCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFormatIsInvalid(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willThrow(new InvalidArgumentException('The --output-format option MUST be one of: text, json.'));
        $this->changelogManager->addEntry(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The --output-format option MUST be one of: text, json.');

        $this->invokeExecute();
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
