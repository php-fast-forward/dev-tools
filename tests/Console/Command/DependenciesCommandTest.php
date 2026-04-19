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

use FastForward\DevTools\Console\Command\DependenciesCommand;
use FastForward\DevTools\Dependency\DependencyUpgradeProcessFactoryInterface;
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

    private ObjectProphecy $upgradeProcessFactory;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $processUnused;

    private ObjectProphecy $processDepAnalyser;

    private ObjectProphecy $processBreakpoint;

    private ObjectProphecy $processUpgrade;

    private DependenciesCommand $command;

    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->upgradeProcessFactory = $this->prophesize(DependencyUpgradeProcessFactoryInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->processUnused = $this->prophesize(Process::class);
        $this->processDepAnalyser = $this->prophesize(Process::class);
        $this->processBreakpoint = $this->prophesize(Process::class);
        $this->processUpgrade = $this->prophesize(Process::class);

        $this->command = new DependenciesCommand(
            $this->upgradeProcessFactory->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->fileLocator->reveal(),
        );
    }

    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('dependencies', $this->command->getName());
        self::assertSame('Analyzes missing, unused, and outdated Composer dependencies.', $this->command->getDescription());
        self::assertSame(
            'This command runs composer-dependency-analyser, composer-unused, and Jack to report missing, unused, and outdated Composer dependencies.',
            $this->command->getHelp()
        );
    }

    #[Test]
    public function executeWillReturnSuccessWhenPreviewAndAnalyzersSucceed(): void
    {
        $this->configureBaseExecution(maxOutdated: '5', fix: false, dev: false);
        $this->upgradeProcessFactory->create(false, false)
            ->willReturn([$this->processUpgrade->reveal()]);
        $this->configureAnalyzerBuilders('5');

        $this->processQueue->add($this->processUpgrade->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processUnused->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency dry-run upgrade preview and analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->configureBaseExecution(maxOutdated: '5', fix: false, dev: true);
        $this->upgradeProcessFactory->create(false, true)
            ->willReturn([]);
        $this->configureAnalyzerBuilders('5');

        $this->processQueue->add($this->processUnused->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::FAILURE)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency dry-run upgrade preview and analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::FAILURE, $this->executeCommand());
    }

    #[Test]
    public function executeWillQueueUpgradeWorkflowBeforeAnalysisWhenFixIsRequested(): void
    {
        $this->configureBaseExecution(maxOutdated: '8', fix: true, dev: true);
        $this->upgradeProcessFactory->create(true, true)
            ->willReturn([$this->processUpgrade->reveal()]);
        $this->configureAnalyzerBuilders('8');

        $this->processQueue->add($this->processUpgrade->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processUnused->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency upgrade and analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function executeWillFailWhenMaxOutdatedIsNotNumeric(): void
    {
        $this->input->getOption('max-outdated')->willReturn('invalid');
        $this->output->writeln('<error>The --max-outdated option MUST be a numeric threshold.</error>')
            ->shouldBeCalledOnce();
        $this->fileLocator->locate(Argument::cetera())->shouldNotBeCalled();
        $this->upgradeProcessFactory->create(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->run(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(DependenciesCommand::FAILURE, $this->executeCommand());
    }

    private function configureBaseExecution(string $maxOutdated, bool $fix, bool $dev): void
    {
        $this->input->getOption('max-outdated')->willReturn($maxOutdated);
        $this->input->getOption('fix')->willReturn($fix);
        $this->input->getOption('dev')->willReturn($dev);
        $this->fileLocator->locate('composer.json')->willReturn('/path/to/composer.json');
    }

    private function configureAnalyzerBuilders(string $maxOutdated): void
    {
        $composerDependencyAnalyserBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $composerDependencyAnalyserFinalBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $jackBreakpointBuilder = $this->prophesize(ProcessBuilderInterface::class);

        $this->processBuilder->build('vendor/bin/composer-unused')
            ->willReturn($this->processUnused->reveal());
        $this->processBuilder->withArgument('--ignore-unused-deps')
            ->willReturn($composerDependencyAnalyserBuilder->reveal());
        $composerDependencyAnalyserBuilder->withArgument('--ignore-prod-only-in-dev-deps')
            ->willReturn($composerDependencyAnalyserFinalBuilder->reveal());
        $composerDependencyAnalyserFinalBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($this->processDepAnalyser->reveal());
        $this->processBuilder->withArgument('--limit', $maxOutdated)
            ->willReturn($jackBreakpointBuilder->reveal());
        $jackBreakpointBuilder->build('vendor/bin/jack breakpoint')
            ->willReturn($this->processBreakpoint->reveal());
    }

    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
