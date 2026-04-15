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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\CodeStyleCommand;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(CodeStyleCommand::class)]
final class CodeStyleCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $process;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $processBuilder;

    private CodeStyleCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->processBuilder->withArgument(Argument::any())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build(Argument::any())
            ->willReturn($this->process->reveal());

        $this->command = new CodeStyleCommand(
            $this->fileLocator->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('code-style', $this->command->getName());
        self::assertSame(
            'Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandCanBeConstructedWithDependencies(): void
    {
        self::assertInstanceOf(CodeStyleCommand::class, $this->command);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->input->getOption('fix')
            ->willReturn(false);

        $this->fileLocator->locate(CodeStyleCommand::CONFIG)
            ->willReturn('/path/to/ecs.php');

        $this->processQueue->run($this->output->reveal())
            ->willReturn(CodeStyleCommand::SUCCESS)
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(CodeStyleCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->input->getOption('fix')
            ->willReturn(false);

        $this->fileLocator->locate(CodeStyleCommand::CONFIG)
            ->willReturn('/path/to/ecs.php');

        $this->processQueue->run($this->output->reveal())
            ->willReturn(CodeStyleCommand::FAILURE)
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(CodeStyleCommand::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunWithFixOptionWhenFixIsEnabled(): void
    {
        $this->input->getOption('fix')
            ->willReturn(true);

        $this->fileLocator->locate(CodeStyleCommand::CONFIG)
            ->willReturn('/path/to/ecs.php');

        $this->processQueue->run($this->output->reveal())
            ->willReturn(CodeStyleCommand::SUCCESS)
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(CodeStyleCommand::SUCCESS, $result);
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledTimes(3);

        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
