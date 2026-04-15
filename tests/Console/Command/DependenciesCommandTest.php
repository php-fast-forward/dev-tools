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

use FastForward\DevTools\Console\Command\DependenciesCommand;
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

#[CoversClass(DependenciesCommand::class)]
final class DependenciesCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $processUnused;

    private ObjectProphecy $processDepAnalyser;

    private DependenciesCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->processUnused = $this->prophesize(Process::class);
        $this->processDepAnalyser = $this->prophesize(Process::class);

        $this->processBuilder->withArgument(Argument::any())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build(Argument::any())
            ->willReturn($this->processUnused->reveal());

        $this->command = new DependenciesCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->fileLocator->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('dependencies', $this->command->getName());
        self::assertSame(
            'Analyzes missing and unused Composer dependencies.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command runs composer-dependency-analyser and composer-unused to report missing and unused Composer dependencies.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandCanBeConstructedWithDependencies(): void
    {
        self::assertInstanceOf(DependenciesCommand::class, $this->command);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenBothToolsSucceed(): void
    {
        $this->fileLocator->locate('composer.json')
            ->willReturn('/path/to/composer.json');

        $this->processBuilder->build('vendor/bin/composer-unused')
            ->willReturn($this->processUnused->reveal());
        $this->processBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($this->processDepAnalyser->reveal());

        $this->processQueue->add($this->processUnused->reveal())
            ->shouldBeCalled();
        $this->processQueue->add($this->processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(DependenciesCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->fileLocator->locate('composer.json')
            ->willReturn('/path/to/composer.json');

        $this->processBuilder->build('vendor/bin/composer-unused')
            ->willReturn($this->processUnused->reveal());
        $this->processBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($this->processDepAnalyser->reveal());

        $this->processQueue->add($this->processUnused->reveal())
            ->shouldBeCalled();
        $this->processQueue->add($this->processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::FAILURE)
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(DependenciesCommand::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCallBothDependencyToolsWithComposerJson(): void
    {
        $composerJsonPath = '/path/to/composer.json';

        $this->fileLocator->locate('composer.json')
            ->willReturn($composerJsonPath);

        $processUnused = $this->prophesize(Process::class);
        $processUnused->getCommandLine()
            ->willReturn('vendor/bin/composer-unused ' . $composerJsonPath . ' --no-progress');

        $processDepAnalyser = $this->prophesize(Process::class);
        $processDepAnalyser->getCommandLine()
            ->willReturn(
                'vendor/bin/composer-dependency-analyser --composer-json=' . $composerJsonPath . ' --ignore-unused-deps --ignore-prod-only-in-dev-deps'
            );

        $this->processBuilder->build('vendor/bin/composer-unused')
            ->willReturn($processUnused->reveal());
        $this->processBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($processDepAnalyser->reveal());

        $this->processQueue->add($processUnused->reveal())
            ->shouldBeCalled();
        $this->processQueue->add($processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(DependenciesCommand::SUCCESS, $result);
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