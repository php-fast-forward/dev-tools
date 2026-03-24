<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Command;

use Composer\Console\Application;
use ReflectionMethod;
use FastForward\DevTools\Command\AbstractCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

abstract class AbstractCommandTestCase extends TestCase
{
    use ProphecyTrait;

    protected AbstractCommand $command;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    protected ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    protected ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    protected ObjectProphecy $output;

    /**
     * @var ObjectProphecy<ProcessHelper>
     */
    protected ObjectProphecy $processHelper;

    protected ObjectProphecy $application;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->processHelper = $this->prophesize(ProcessHelper::class);
        $this->application = $this->prophesize(Application::class);

        $this->filesystem->isAbsolutePath(Argument::any())->willReturn(false);

        $this->processHelper->getName()
            ->willReturn('process');
        $this->processHelper->setHelperSet(Argument::type(HelperSet::class))->shouldBeCalledOnce();
        $this->processHelper
            ->run(Argument::type(Process::class), Argument::any())
            ->willReturn(0);

        $helperSet = new HelperSet([
            'process' => $this->processHelper->reveal(),
        ]);

        $this->application->getHelperSet()
            ->willReturn($helperSet);
        $this->application->getInitialWorkingDirectory()
            ->willReturn(getcwd());

        $this->command = new ($this->getCommandClass())($this->filesystem->reveal());
        $this->command->setHelperSet($helperSet);

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()
            ->willReturn(true);

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::type(Process::class), Argument::cetera())
            ->willReturn($process->reveal());

        $this->command->setApplication($this->application->reveal());

        $arguments = $this->command->getDefinition()
            ->getArguments();
        $options = $this->command->getDefinition()
            ->getOptions();

        foreach ($arguments as $argument) {
            $this->input->getArgument($argument->getName())
                ->willReturn($argument->getDefault());
        }

        foreach ($options as $option) {
            $this->input->getOption($option->getName())
                ->willReturn($option->getDefault());
        }
    }

    /**
     * @return string
     */
    abstract protected function getCommandClass(): string;

    /**
     * @return string
     */
    abstract protected function getCommandName(): string;

    /**
     * @return string
     */
    abstract protected function getCommandDescription(): string;

    /**
     * @return string
     */
    abstract protected function getCommandHelp(): string;

    /**
     * @return void
     */
    #[Test]
    public function configureWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame($this->getCommandName(), $this->command->getName());
        self::assertSame($this->getCommandDescription(), $this->command->getDescription());
        self::assertSame($this->getCommandHelp(), $this->command->getHelp());
    }

    /**
     * @return int
     */
    protected function invokeExecute(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }

    /**
     * @param callable $callback
     * @param bool $isSuccessful
     *
     * @return void
     */
    protected function willRunProcessWithCallback(callable $callback, bool $isSuccessful = true): void
    {
        $process = $this->prophesize(Process::class);
        $process->isSuccessful()
            ->willReturn($isSuccessful)
            ->shouldBeCalled();

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that($callback), Argument::cetera())
            ->willReturn($process->reveal())
            ->shouldBeCalled();
    }

    /**
     * @param string $filename
     * @param bool $local
     *
     * @return void
     */
    protected function withConfigFile(string $filename, bool $local = false): void
    {
        $this->filesystem->exists(getcwd() . '/' . $filename)->willReturn($local);
        $this->filesystem->exists(getcwd() . '/' . $filename, true)->willReturn(true);
    }
}
