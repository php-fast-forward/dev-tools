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

use FastForward\DevTools\Console\Command\SyncCommand;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(SyncCommand::class)]
#[UsesClass(ProcessBuilder::class)]
final class SyncCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $logger;

    private SyncCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->input->getOption(Argument::type('string'))->willReturn(false);
        $this->input->getOption('output-format')
            ->willReturn('text');

        $this->command = new SyncCommand(
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillQueueDedicatedSynchronizationCommands(): void
    {
        $this->processQueue->add(Argument::type(Process::class), false, false)->shouldBeCalledTimes(2);
        $this->processQueue->add(Argument::type(Process::class), false, true)->shouldBeCalledTimes(11);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(SyncCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Starting dev-tools synchronization...')
            ->shouldBeCalled();
        $this->logger->info(
            'Dev-tools synchronization completed successfully.',
            [
                'command' => 'dev-tools:sync',
                'overwrite' => false,
                'dry_run' => false,
                'check' => false,
                'interactive' => false,
                'skipped_destructive_syncs' => false,
                'process_output' => null,
            ],
        )->shouldBeCalled();

        self::assertSame(SyncCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillDisableDetachedModeWhenCheckingDrift(): void
    {
        $this->input->getOption('check')
            ->willReturn(true);
        $this->processQueue->add(Argument::type(Process::class), false, false)->shouldBeCalledTimes(10);
        $this->processQueue->add(Argument::type(Process::class), false, true)->shouldNotBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(SyncCommand::FAILURE)
            ->shouldBeCalledOnce();
        $this->logger->info('Starting dev-tools synchronization...')
            ->shouldBeCalled();
        $this->logger->warning(
            'Skipping wiki, skills, and agents during preview/check modes because they do not yet expose non-destructive verification.'
        )->shouldBeCalled();
        $this->logger->error(
            'Dev-tools synchronization failed.',
            [
                'command' => 'dev-tools:sync',
                'overwrite' => false,
                'dry_run' => false,
                'check' => true,
                'interactive' => false,
                'skipped_destructive_syncs' => true,
                'process_output' => null,
            ],
        )->shouldBeCalled();

        self::assertSame(SyncCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPropagateJsonOutputFormatToSubCommands(): void
    {
        $this->input->getOption('output-format')
            ->willReturn('json');
        $this->processQueue->add(Argument::type(Process::class), false, false)->shouldBeCalledTimes(2);
        $this->processQueue->add(
            Argument::that(
                static fn(Process $process): bool => str_contains($process->getCommandLine(), '--output-format=json')
            ),
            false,
            true,
        )->shouldBeCalledTimes(11);
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(SyncCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Starting dev-tools synchronization...')
            ->shouldBeCalled();
        $this->logger->info(
            'Dev-tools synchronization completed successfully.',
            Argument::that(static fn(array $context): bool => 'dev-tools:sync' === $context['command']
                && \is_string($context['process_output'])),
        )->shouldBeCalled();

        self::assertSame(SyncCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
