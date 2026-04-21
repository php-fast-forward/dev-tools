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
use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRendererInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\OutputFormatResolverInterface;
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
#[CoversClass(CommandResult::class)]
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
    private ObjectProphecy $outputFormatResolver;

    /**
     * @var ObjectProphecy<CommandResultRendererInterface>
     */
    private ObjectProphecy $commandResultRenderer;

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
        $this->outputFormatResolver = $this->prophesize(OutputFormatResolverInterface::class);
        $this->commandResultRenderer = $this->prophesize(CommandResultRendererInterface::class);
        $this->input->getOption('against')
            ->willReturn(null);
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');
        $this->command = new ChangelogCheckCommand(
            $this->filesystem->reveal(),
            $this->checker->reveal(),
            $this->outputFormatResolver->reveal(),
            $this->commandResultRenderer->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenUnreleasedEntriesExist(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willReturn(OutputFormat::TEXT);
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(true);
        $this->commandResultRenderer->render(
            $this->output->reveal(),
            Argument::that(static fn(object $result): bool => 'success' === $result->status
                && 'CHANGELOG.md contains unreleased changes ready for review.' === $result->message
                && [
                    'command' => 'changelog:check',
                    'file' => 'CHANGELOG.md',
                    'against' => null,
                    'has_pending_changes' => true,
                ] === $result->context),
            OutputFormat::TEXT,
        )->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenUnreleasedEntriesAreMissing(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willReturn(OutputFormat::TEXT);
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(false);
        $this->commandResultRenderer->render(
            $this->output->reveal(),
            Argument::that(static fn(object $result): bool => 'failure' === $result->status
                && 'CHANGELOG.md must add a meaningful entry to the Unreleased section.' === $result->message
                && [
                    'command' => 'changelog:check',
                    'file' => 'CHANGELOG.md',
                    'against' => null,
                    'has_pending_changes' => false,
                ] === $result->context),
            OutputFormat::TEXT,
        )->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRenderJsonOutputWhenRequested(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willReturn(OutputFormat::JSON);
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(true);
        $this->commandResultRenderer->render(
            $this->output->reveal(),
            Argument::that(static fn(object $result): bool => 'success' === $result->status),
            OutputFormat::JSON,
        )->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFormatIsInvalid(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willThrow(new InvalidArgumentException('The --format option MUST be one of: text, json.'));
        $this->output->writeln('<error>The --format option MUST be one of: text, json.</error>')
            ->shouldBeCalled();
        $this->checker->hasPendingChanges(Argument::cetera())
            ->shouldNotBeCalled();
        $this->commandResultRenderer->render(Argument::cetera())
            ->shouldNotBeCalled();

        self::assertSame(ChangelogCheckCommand::FAILURE, $this->invokeExecute());
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
