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

use Composer\Console\Application;
use Composer\IO\IOInterface;
use FastForward\DevTools\Console\Command\SkillsCommand;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Sync\PackagedDirectorySynchronizer;
use FastForward\DevTools\Sync\SynchronizeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

#[CoversClass(SkillsCommand::class)]
#[CoversClass(OutputFormat::class)]
#[UsesClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class SkillsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PackagedDirectorySynchronizer>
     */
    private ObjectProphecy $synchronizer;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<Application>
     */
    private ObjectProphecy $application;

    /**
     * @var ObjectProphecy<IOInterface>
     */
    private ObjectProphecy $io;

    /**
     * @var ObjectProphecy<CommandResponderFactoryInterface>
     */
    private ObjectProphecy $commandResponderFactory;

    /**
     * @var ObjectProphecy<CommandResponderInterface>
     */
    private ObjectProphecy $commandResponder;

    private SkillsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->synchronizer = $this->prophesize(PackagedDirectorySynchronizer::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->application = $this->prophesize(Application::class);
        $this->io = $this->prophesize(IOInterface::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);

        $this->application->getHelperSet()
            ->willReturn(new HelperSet());

        $this->command = new SkillsCommand(
            $this->synchronizer->reveal(),
            $this->filesystem->reveal(),
            $this->commandResponderFactory->reveal(),
        );
        $this->command->setApplication($this->application->reveal());

        $this->application->getIO()
            ->willReturn($this->io->reveal());
        $this->filesystem->getAbsolutePath('.agents/skills', \dirname(__DIR__, 3))
            ->willReturn(getcwd() . '/.agents/skills');
        $this->filesystem->getAbsolutePath('.agents/skills')
            ->willReturn(getcwd() . '/.agents/skills');
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->commandResponder->format()
            ->willReturn(OutputFormat::TEXT);
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('skills', $this->command->getName());
        self::assertSame(
            'Synchronizes Fast Forward skills into .agents/skills directory.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command ensures the consumer repository contains linked Fast Forward skills by creating symlinks to the packaged skills and removing broken links.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenPackagedSkillsDirectoryDoesNotExist(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';

        $this->filesystem->exists($skillsPath)
            ->willReturn(false);
        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger(Argument::cetera())->shouldNotBeCalled();
        $this->synchronizer->synchronize(Argument::cetera())->shouldNotBeCalled();
        $this->commandResponder->failure(
            'No packaged skills found at: ' . $skillsPath,
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => false,
            ],
        )->willReturn(SkillsCommand::FAILURE)->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateSkillsDirectoryWhenItDoesNotExist(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';
        $result = new SynchronizeResult();

        $this->filesystem->exists($skillsPath)
            ->willReturn(true, false);
        $this->filesystem->mkdir($skillsPath)
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($skillsPath, $skillsPath, '.agents/skills')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Created .agents/skills directory.</info>')
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Skills synchronization completed successfully.',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => true,
            ],
        )->willReturn(SkillsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenSynchronizerFails(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';
        $result = new SynchronizeResult();
        $result->markFailed();

        $this->filesystem->exists($skillsPath)
            ->willReturn(true, true);
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($skillsPath, $skillsPath, '.agents/skills')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->commandResponder->failure(
            'Skills synchronization failed.',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => false,
            ],
        )->willReturn(SkillsCommand::FAILURE)->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSuppressProgressMessagesWhenJsonOutputIsRequested(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';
        $result = new SynchronizeResult();

        $this->commandResponder->format()
            ->willReturn(OutputFormat::JSON);
        $this->filesystem->exists($skillsPath)
            ->willReturn(true, true);
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($skillsPath, $skillsPath, '.agents/skills')
            ->willReturn($result)
            ->shouldBeCalledOnce();
        $this->output->writeln(Argument::cetera())
            ->shouldNotBeCalled();
        $this->commandResponder->success(
            'Skills synchronization completed successfully.',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => false,
            ],
        )->willReturn(SkillsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
