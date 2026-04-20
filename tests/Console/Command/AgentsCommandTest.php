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
use FastForward\DevTools\Console\Command\AgentsCommand;
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

#[CoversClass(AgentsCommand::class)]
#[UsesClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class AgentsCommandTest extends TestCase
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

    private AgentsCommand $command;

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

        $this->application->getHelperSet()
            ->willReturn(new HelperSet());

        $this->command = new AgentsCommand($this->synchronizer->reveal(), $this->filesystem->reveal());
        $this->command->setApplication($this->application->reveal());

        $this->application->getIO()
            ->willReturn($this->io->reveal());
        $this->filesystem->getAbsolutePath('.agents/agents', \dirname(__DIR__, 3))
            ->willReturn(getcwd() . '/.agents/agents');
        $this->filesystem->getAbsolutePath('.agents/agents')
            ->willReturn(getcwd() . '/.agents/agents');
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('agents', $this->command->getName());
        self::assertSame(
            'Synchronizes Fast Forward project agents into .agents/agents directory.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command ensures the consumer repository contains linked Fast Forward project agents by creating symlinks to the packaged prompts and removing broken links.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenPackagedAgentsDirectoryDoesNotExist(): void
    {
        $agentsPath = getcwd() . '/.agents/agents';

        $this->filesystem->exists($agentsPath)
            ->willReturn(false);
        $this->output->writeln('<info>Starting agents synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<comment>No packaged .agents/agents found at: ' . $agentsPath . '</comment>')
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger(Argument::cetera())->shouldNotBeCalled();
        $this->synchronizer->synchronize(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(AgentsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateAgentsDirectoryWhenItDoesNotExist(): void
    {
        $agentsPath = getcwd() . '/.agents/agents';
        $result = new SynchronizeResult();

        $this->filesystem->exists($agentsPath)
            ->willReturn(true, false);
        $this->filesystem->mkdir($agentsPath)
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($agentsPath, $agentsPath, '.agents/agents')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting agents synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Created .agents/agents directory.</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Agents synchronization completed successfully.</info>')
            ->shouldBeCalledOnce();

        self::assertSame(AgentsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenSynchronizerFails(): void
    {
        $agentsPath = getcwd() . '/.agents/agents';
        $result = new SynchronizeResult();
        $result->markFailed();

        $this->filesystem->exists($agentsPath)
            ->willReturn(true, true);
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($agentsPath, $agentsPath, '.agents/agents')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting agents synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<error>Agents synchronization failed.</error>')
            ->shouldBeCalledOnce();

        self::assertSame(AgentsCommand::FAILURE, $this->invokeExecute());
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
