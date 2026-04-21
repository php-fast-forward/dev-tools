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
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

#[CoversClass(SkillsCommand::class)]
#[UsesClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class SkillsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $synchronizer;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $application;

    private ObjectProphecy $io;

    private SkillsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->synchronizer = $this->prophesize(PackagedDirectorySynchronizer::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->application = $this->prophesize(Application::class);
        $this->io = $this->prophesize(IOInterface::class);

        $this->application->getHelperSet()
            ->willReturn(new HelperSet());
        $this->application->getIO()
            ->willReturn($this->io->reveal());
        $this->filesystem->getAbsolutePath('.agents/skills', \dirname(__DIR__, 3))
            ->willReturn(getcwd() . '/.agents/skills');
        $this->filesystem->getAbsolutePath('.agents/skills')
            ->willReturn(getcwd() . '/.agents/skills');

        $this->command = new SkillsCommand(
            $this->synchronizer->reveal(),
            $this->filesystem->reveal(),
            $this->logger->reveal(),
        );
        $this->command->setApplication($this->application->reveal());
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
        $this->synchronizer->setLogger(Argument::cetera())->shouldNotBeCalled();
        $this->synchronizer->synchronize(Argument::cetera())->shouldNotBeCalled();
        $this->logger->info('Starting skills synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->error(
            'No packaged skills found at: {packaged_skills_path}',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => false,
            ],
        )->shouldBeCalledOnce();

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
        $this->logger->info('Starting skills synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->info('Created .agents/skills directory.')
            ->shouldBeCalledOnce();
        $this->logger->info(
            'Skills synchronization completed successfully.',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => true,
            ],
        )->shouldBeCalledOnce();

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
        $this->logger->info('Starting skills synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->error(
            'Skills synchronization failed.',
            [
                'command' => 'skills',
                'packaged_skills_path' => $skillsPath,
                'skills_dir' => $skillsPath,
                'directory_created' => false,
            ],
        )->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::FAILURE, $this->invokeExecute());
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
