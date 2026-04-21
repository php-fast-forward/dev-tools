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
use FastForward\DevTools\Changelog\Checker\UnreleasedEntryCheckerInterface;
use FastForward\DevTools\Console\Command\ChangelogCheckCommand;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\ResolvedCommandResponderInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogCheckCommand::class)]
#[CoversClass(OutputFormat::class)]
final class ChangelogCheckCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UnreleasedEntryCheckerInterface>
     */
    private ObjectProphecy $checker;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<OutputFormatResolverInterface>
     */
    private ObjectProphecy $commandResponder;

    /**
     * @var ObjectProphecy<ResolvedCommandResponderInterface>
     */
    private ObjectProphecy $resolvedCommandResponder;

    private ChangelogCheckCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->checker = $this->prophesize(UnreleasedEntryCheckerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);
        $this->resolvedCommandResponder = $this->prophesize(ResolvedCommandResponderInterface::class);
        $this->input->getOption('against')
            ->willReturn(null);
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');
        $this->command = new ChangelogCheckCommand(
            $this->filesystem->reveal(),
            $this->checker->reveal(),
            $this->commandResponder->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenUnreleasedEntriesExist(): void
    {
        $this->commandResponder->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->resolvedCommandResponder->reveal());
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(true);
        $this->resolvedCommandResponder->success(
            'CHANGELOG.md contains unreleased changes ready for review.',
            [
                'command' => 'changelog:check',
                'file' => 'CHANGELOG.md',
                'against' => null,
                'has_pending_changes' => true,
            ],
            ChangelogCheckCommand::SUCCESS,
        )->willReturn(ChangelogCheckCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenUnreleasedEntriesAreMissing(): void
    {
        $this->commandResponder->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->resolvedCommandResponder->reveal());
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(false);
        $this->resolvedCommandResponder->failure(
            'CHANGELOG.md must add a meaningful entry to the Unreleased section.',
            [
                'command' => 'changelog:check',
                'file' => 'CHANGELOG.md',
                'against' => null,
                'has_pending_changes' => false,
            ],
            ChangelogCheckCommand::FAILURE,
        )->willReturn(ChangelogCheckCommand::FAILURE)->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRenderJsonOutputWhenRequested(): void
    {
        $this->commandResponder->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->resolvedCommandResponder->reveal());
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(true);
        $this->resolvedCommandResponder->success(
            'CHANGELOG.md contains unreleased changes ready for review.',
            Argument::type('array'),
            ChangelogCheckCommand::SUCCESS,
        )->willReturn(ChangelogCheckCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFormatIsInvalid(): void
    {
        $this->commandResponder->from($this->input->reveal(), $this->output->reveal())
            ->willThrow(new InvalidArgumentException('The --format option MUST be one of: text, json.'));
        $this->checker->hasPendingChanges(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The --format option MUST be one of: text, json.');

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
