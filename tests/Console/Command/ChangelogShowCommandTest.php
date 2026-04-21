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

use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogShowCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogShowCommand::class)]
final class ChangelogShowCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $changelogManager;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ChangelogShowCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getArgument('version')
            ->willReturn('1.2.0');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogShowCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillLogReleaseNotes(): void
    {
        $releaseNotes = "### Added\n\n- Ship it\n";

        $this->changelogManager->renderReleaseNotes('/repo/CHANGELOG.md', '1.2.0')
            ->willReturn($releaseNotes)
            ->shouldBeCalled();
        $this->logger->info(
            $releaseNotes,
            [
                'input' => $this->input->reveal(),
                'version' => '1.2.0',
                'release_notes' => $releaseNotes,
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogShowCommand::SUCCESS, $this->invokeExecute());
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
