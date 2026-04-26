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

use FastForward\DevTools\Console\Command\AgentsCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Sync\PackagedDirectorySynchronizer;
use FastForward\DevTools\Sync\SynchronizeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

#[CoversClass(AgentsCommand::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
#[UsesTrait(LogsCommandResults::class)]
final class AgentsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $synchronizer;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private AgentsCommand $command;

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
        $this->filesystem->getAbsolutePath('.agents/agents')
            ->willReturn(getcwd() . '/.agents/agents');

        $this->command = new AgentsCommand(
            $this->synchronizer->reveal(),
            $this->filesystem->reveal(),
            $this->logger->reveal(),
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
        $this->synchronizer->synchronize(Argument::cetera())->shouldNotBeCalled();
        $this->logger->info('Starting agents synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->error(
            'No packaged .agents/agents found at: {packaged_agents_path}',
            [
                'input' => $this->input->reveal(),
                'file' => null,
                'line' => null,
                'packaged_agents_path' => $agentsPath,
                'agents_dir' => $agentsPath,
                'directory_created' => false,
            ],
        )->shouldBeCalledOnce();

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
        $this->synchronizer->synchronize($agentsPath, $agentsPath, '.agents/agents')
            ->willReturn($result)
            ->shouldBeCalledOnce();
        $this->logger->info('Starting agents synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->info('Created .agents/agents directory.')
            ->shouldBeCalledOnce();
        $this->logger->log(
            'info',
            'Agents synchronization completed successfully.',
            [
                'input' => $this->input->reveal(),
                'packaged_agents_path' => $agentsPath,
                'agents_dir' => $agentsPath,
                'directory_created' => true,
            ],
        )->shouldBeCalledOnce();

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
        $this->synchronizer->synchronize($agentsPath, $agentsPath, '.agents/agents')
            ->willReturn($result)
            ->shouldBeCalledOnce();
        $this->logger->info('Starting agents synchronization...')
            ->shouldBeCalledOnce();
        $this->logger->error(
            'Agents synchronization failed.',
            [
                'input' => $this->input->reveal(),
                'file' => null,
                'line' => null,
                'packaged_agents_path' => $agentsPath,
                'agents_dir' => $agentsPath,
                'directory_created' => false,
            ],
        )->shouldBeCalledOnce();

        self::assertSame(AgentsCommand::FAILURE, $this->invokeExecute());
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
