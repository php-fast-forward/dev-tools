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
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogPromoteCommand;
use FastForward\DevTools\Console\Command\EmitsGithubActionErrors;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogPromoteCommand::class)]
#[UsesTrait(EmitsGithubActionErrors::class)]
final class ChangelogPromoteCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $changelogManager;

    private ObjectProphecy $clock;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ChangelogPromoteCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->clock = $this->prophesize(ClockInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('date')
            ->willReturn(null);
        $this->input->getArgument('version')
            ->willReturn('1.2.0');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');
        $this->clock->now()
            ->willReturn(new DateTimeImmutable('2026-04-21'));

        $this->command = new ChangelogPromoteCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->clock->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillUseTheCurrentDateByDefault(): void
    {
        $this->changelogManager->promote('/repo/CHANGELOG.md', '1.2.0', '2026-04-21')
            ->shouldBeCalled();
        $this->logger->info(
            'Promoted Unreleased changelog entries to [{version}] in {absolute_file}.',
            [
                'input' => $this->input->reveal(),
                'absolute_file' => '/repo/CHANGELOG.md',
                'version' => '1.2.0',
                'date' => '2026-04-21',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogPromoteCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPreferTheExplicitDate(): void
    {
        $this->input->getOption('date')
            ->willReturn('2026-04-20');
        $this->changelogManager->promote('/repo/CHANGELOG.md', '1.2.0', '2026-04-20')
            ->shouldBeCalled();
        $this->logger->info(
            'Promoted Unreleased changelog entries to [{version}] in {absolute_file}.',
            [
                'input' => $this->input->reveal(),
                'absolute_file' => '/repo/CHANGELOG.md',
                'version' => '1.2.0',
                'date' => '2026-04-20',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogPromoteCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenPromotionFails(): void
    {
        $this->changelogManager->promote('/repo/CHANGELOG.md', '1.2.0', '2026-04-21')
            ->willThrow(new RuntimeException('Unable to promote changelog.'));
        $this->logger->error(
            'Unable to promote the changelog release.',
            [
                'input' => $this->input->reveal(),
                'exception_message' => 'Unable to promote changelog.',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogPromoteCommand::FAILURE, $this->invokeExecute());
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
