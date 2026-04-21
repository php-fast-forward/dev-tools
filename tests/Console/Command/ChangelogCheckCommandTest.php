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

use FastForward\DevTools\Changelog\Checker\UnreleasedEntryCheckerInterface;
use FastForward\DevTools\Console\Command\ChangelogCheckCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogCheckCommand::class)]
#[UsesTrait(LogsCommandResults::class)]
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

    private ObjectProphecy $logger;

    private ChangelogCheckCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->checker = $this->prophesize(UnreleasedEntryCheckerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->input->getOption('against')
            ->willReturn(null);
        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');
        $this->command = new ChangelogCheckCommand(
            $this->filesystem->reveal(),
            $this->checker->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenUnreleasedEntriesExist(): void
    {
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(true);
        $this->logger->log(
            'info',
            'The changelog contains unreleased changes ready for review.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && true === $context['has_pending_changes']),
        )->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenUnreleasedEntriesAreMissing(): void
    {
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', null)
            ->willReturn(false);
        $this->logger->error(
            'The changelog must add a meaningful entry to the Unreleased section.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && false === $context['has_pending_changes']),
        )->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillLogFailureContextWhenAgainstReferenceIsProvided(): void
    {
        $this->input->getOption('against')
            ->willReturn('origin/main');
        $this->checker->hasPendingChanges('/repo/CHANGELOG.md', 'origin/main')
            ->willReturn(false);
        $this->logger->error(
            'The changelog must add a meaningful entry to the Unreleased section.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && false === $context['has_pending_changes']),
        )->shouldBeCalled();

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
