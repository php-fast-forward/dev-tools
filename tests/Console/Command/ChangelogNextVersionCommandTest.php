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
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogNextVersionCommand;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
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

#[CoversClass(ChangelogNextVersionCommand::class)]
#[CoversClass(OutputFormat::class)]
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

    private ChangelogNextVersionCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);

        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getOption('current-version')
            ->willReturn(null);
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogNextVersionCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->commandResponderFactory->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWithTheInferredVersion(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', null)
            ->willReturn('1.3.0');
        $this->commandResponder->success(
            '1.3.0',
            [
                'command' => 'changelog:next-version',
                'file' => 'CHANGELOG.md',
                'current_version' => null,
                'next_version' => '1.3.0',
            ],
        )->willReturn(ChangelogNextVersionCommand::SUCCESS)->shouldBeCalled();

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
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', '1.2.3')
            ->willReturn('2.0.0');
        $this->commandResponder->success(
            '2.0.0',
            [
                'command' => 'changelog:next-version',
                'file' => 'CHANGELOG.md',
                'current_version' => '1.2.3',
                'next_version' => '2.0.0',
            ],
        )->willReturn(ChangelogNextVersionCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogNextVersionCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenJsonOutputIsRequested(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->changelogManager->inferNextVersion('/repo/CHANGELOG.md', null)
            ->willReturn('1.3.0');
        $this->commandResponder->success(
            '1.3.0',
            Argument::type('array'),
        )->willReturn(ChangelogNextVersionCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(ChangelogNextVersionCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFormatIsInvalid(): void
    {
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willThrow(new InvalidArgumentException('The --format option MUST be one of: text, json.'));
        $this->changelogManager->inferNextVersion(Argument::cetera())
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
