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
use FastForward\DevTools\Console\Command\ChangelogNextVersionCommand;
use FastForward\DevTools\Console\Command\Traits\HasGithubActionOutput;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ChangelogNextVersionCommand::class)]
#[UsesTrait(HasGithubActionOutput::class)]
#[UsesTrait(LogsCommandResults::class)]
final class ChangelogNextVersionCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ChangelogManagerInterface>
     */
    private ObjectProphecy $changelogManager;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ChangelogNextVersionCommand $command;

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
        $this->input->getOption('current-version')
            ->willReturn(null);
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogNextVersionCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWithTheInferredVersion(): void
    {
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', null)
            ->willReturn('1.3.0');
        $this->logger->log(
            'info',
            '1.3.0',
            [
                'input' => $this->input->reveal(),
                'current_version' => null,
                'next_version' => '1.3.0',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogNextVersionCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPassTheExplicitCurrentVersionToInference(): void
    {
        $this->input->getOption('current-version')
            ->willReturn('1.2.3');
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', '1.2.3')
            ->willReturn('2.0.0');
        $this->logger->log(
            'info',
            '2.0.0',
            [
                'input' => $this->input->reveal(),
                'current_version' => '1.2.3',
                'next_version' => '2.0.0',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogNextVersionCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenVersionInferenceFails(): void
    {
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', null)
            ->willThrow(new RuntimeException('Unable to parse changelog.'));
        $this->logger->error(
            'Unable to infer the next changelog version.',
            [
                'input' => $this->input->reveal(),
                'exception_message' => 'Unable to parse changelog.',
            ],
        )->shouldBeCalled();

        self::assertSame(ChangelogNextVersionCommand::FAILURE, $this->invokeExecute());
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
