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

use FastForward\DevTools\Console\Command\DependenciesCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\getcwd;
use function str_contains;

#[CoversClass(DependenciesCommand::class)]
final class DependenciesCommandTest extends AbstractCommandTestCase
{
    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return DependenciesCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'dependencies';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Analyzes missing and unused Composer dependencies.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs composer-dependency-analyser and composer-unused to report missing and unused Composer dependencies.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->output->writeln(Argument::type('string'));

        $cwd = getcwd();
        $this->filesystem->exists($cwd . '/composer.json')->willReturn(true);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenBothToolsSucceed(): void
    {
        $processUnused = $this->prophesize(Process::class);
        $processUnused->isSuccessful()
            ->willReturn(true);

        $processDepAnalyser = $this->prophesize(Process::class);
        $processDepAnalyser->isSuccessful()
            ->willReturn(true);

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-unused')
            ), Argument::cetera())
            ->willReturn($processUnused->reveal())
            ->shouldBeCalled();

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-dependency-analyser')
            ), Argument::cetera())
            ->willReturn($processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        self::assertSame(DependenciesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFirstToolFails(): void
    {
        $processUnused = $this->prophesize(Process::class);
        $processUnused->isSuccessful()
            ->willReturn(false);

        $processDepAnalyser = $this->prophesize(Process::class);
        $processDepAnalyser->isSuccessful()
            ->willReturn(true);

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-unused')
            ), Argument::cetera())
            ->willReturn($processUnused->reveal())
            ->shouldBeCalled();

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-dependency-analyser')
            ), Argument::cetera())
            ->willReturn($processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        self::assertSame(DependenciesCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenSecondToolFails(): void
    {
        $processUnused = $this->prophesize(Process::class);
        $processUnused->isSuccessful()
            ->willReturn(true);

        $processDepAnalyser = $this->prophesize(Process::class);
        $processDepAnalyser->isSuccessful()
            ->willReturn(false);

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-unused')
            ), Argument::cetera())
            ->willReturn($processUnused->reveal())
            ->shouldBeCalled();

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-dependency-analyser')
            ), Argument::cetera())
            ->willReturn($processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        self::assertSame(DependenciesCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCallBothDependencyToolsWithComposerJson(): void
    {
        $cwd = getcwd();
        $composerJsonPath = $cwd . '/composer.json';

        $this->filesystem->exists($composerJsonPath)
            ->willReturn(true);

        $processUnused = $this->prophesize(Process::class);
        $processUnused->isSuccessful()
            ->willReturn(true);
        $processUnused->getCommandLine()
            ->willReturn('vendor/bin/composer-unused ' . $composerJsonPath . ' --no-progress');

        $processDepAnalyser = $this->prophesize(Process::class);
        $processDepAnalyser->isSuccessful()
            ->willReturn(true);
        $processDepAnalyser->getCommandLine()
            ->willReturn(
                'vendor/bin/composer-dependency-analyser --composer-json=' . $composerJsonPath . ' --ignore-unused-deps --ignore-prod-only-in-dev-deps'
            );

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-unused')
            ), Argument::cetera())
            ->willReturn($processUnused->reveal())
            ->shouldBeCalled();

        $this->processHelper
            ->run(Argument::type(OutputInterface::class), Argument::that(
                static fn(Process $p): bool => str_contains($p->getCommandLine(), 'composer-dependency-analyser')
            ), Argument::cetera())
            ->willReturn($processDepAnalyser->reveal())
            ->shouldBeCalled();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();

        self::assertSame(DependenciesCommand::SUCCESS, $this->invokeExecute());
    }
}
