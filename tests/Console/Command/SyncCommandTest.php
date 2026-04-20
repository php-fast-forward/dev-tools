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

    private SyncCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->input->getOption(Argument::type('string'))
            ->willReturn(false);
        $this->command = new SyncCommand(new ProcessBuilder(), $this->processQueue->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('dev-tools:sync', $this->command->getName());
        self::assertSame(
            'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, CODEOWNERS, .editorconfig, and .gitattributes in the root project.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command runs the dedicated synchronization commands for composer.json, resources, CODEOWNERS, funding metadata, wiki, git metadata, packaged skills, packaged agents, license, and Git hooks.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillQueueDedicatedSynchronizationCommands(): void
    {
        $this->processQueue->add(Argument::type(Process::class), false, false)
            ->shouldBeCalledTimes(2);
        $this->processQueue->add(Argument::type(Process::class), false, true)
            ->shouldBeCalledTimes(11);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(SyncCommand::SUCCESS)
            ->shouldBeCalledOnce();

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

        $this->processQueue->add(Argument::type(Process::class), false, false)
            ->shouldBeCalledTimes(10);
        $this->processQueue->add(Argument::type(Process::class), false, true)
            ->shouldNotBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(SyncCommand::FAILURE)
            ->shouldBeCalledOnce();

        self::assertSame(SyncCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
