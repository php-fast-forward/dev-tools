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

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processOpenVersions;

    private ObjectProphecy $processRaiseToInstalled;

    private ObjectProphecy $processComposerUpdate;

    private ObjectProphecy $processComposerNormalize;

    private ObjectProphecy $processDepAnalyser;

    private ObjectProphecy $processBreakpoint;

    private DependenciesCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processOpenVersions = $this->prophesize(Process::class);
        $this->processRaiseToInstalled = $this->prophesize(Process::class);
        $this->processComposerUpdate = $this->prophesize(Process::class);
        $this->processComposerNormalize = $this->prophesize(Process::class);
        $this->processDepAnalyser = $this->prophesize(Process::class);
        $this->processBreakpoint = $this->prophesize(Process::class);
        $this->fileLocator->locate('composer-dependency-analyser.php')
            ->willReturn('/app/composer-dependency-analyser.php');

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
            'Analyzes missing, unused, misplaced, and outdated Composer dependencies.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command runs composer-dependency-analyser and Jack to report missing, unused, misplaced, and outdated Composer dependencies.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenPreviewAndAnalyzersSucceed(): void
    {
        $this->configureBaseExecution(maxOutdated: '5', upgrade: false, dev: false);
        $this->configurePreviewBuilders(dev: false, maxOutdated: '5');

        $this->processQueue->add($this->processRaiseToInstalled->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processOpenVersions->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->configureBaseExecution(maxOutdated: '5', upgrade: false, dev: true);
        $this->configurePreviewBuilders(dev: true, maxOutdated: '5');

        $this->processQueue->add($this->processRaiseToInstalled->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processOpenVersions->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::FAILURE)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillQueueUpgradeWorkflowBeforeAnalysisWhenUpgradeIsRequested(): void
    {
        $this->configureBaseExecution(maxOutdated: '8', upgrade: true, dev: true);
        $this->configureUpgradeBuilders(dev: true, maxOutdated: '8');

        $this->processQueue->add($this->processRaiseToInstalled->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processOpenVersions->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processComposerUpdate->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processComposerNormalize->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processDepAnalyser->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->processBreakpoint->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenMaxOutdatedIsNotNumeric(): void
    {
        $this->input->getOption('max-outdated')
            ->willReturn('invalid');
        $this->output->writeln('<error>The --max-outdated option MUST be a numeric threshold.</error>')
            ->shouldBeCalledOnce();
        $this->processQueue->run(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(DependenciesCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @param string $maxOutdated
     * @param bool $upgrade
     * @param bool $dev
     *
     * @return void
     */
    private function configureBaseExecution(string $maxOutdated, bool $upgrade, bool $dev): void
    {
        $this->input->getOption('max-outdated')
            ->willReturn($maxOutdated);
        $this->input->getOption('upgrade')
            ->willReturn($upgrade);
        $this->input->getOption('dev')
            ->willReturn($dev);
    }

    /**
     * @param bool $dev
     * @param string $maxOutdated
     *
     * @return void
     */
    private function configurePreviewBuilders(bool $dev, string $maxOutdated): void
    {
        $depAnalyserBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $depAnalyserFinalBuilder = $this->prophesize(ProcessBuilderInterface::class);

        $this->processBuilder
            ->build(
                $dev ? 'vendor/bin/jack raise-to-installed --dev --dry-run' : 'vendor/bin/jack raise-to-installed --dry-run'
            )
            ->willReturn($this->processRaiseToInstalled->reveal());
        $this->processBuilder
            ->build($dev ? 'vendor/bin/jack open-versions --dev --dry-run' : 'vendor/bin/jack open-versions --dry-run')
            ->willReturn($this->processOpenVersions->reveal());
        $this->processBuilder->withArgument('--config', '/app/composer-dependency-analyser.php')
            ->willReturn($depAnalyserBuilder->reveal());
        $depAnalyserBuilder->withArgument('--ignore-prod-only-in-dev-deps')
            ->willReturn($depAnalyserFinalBuilder->reveal());
        $depAnalyserFinalBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($this->processDepAnalyser->reveal());
        $this->processBuilder
            ->build(
                $dev ? 'vendor/bin/jack breakpoint --dev --limit ' . $maxOutdated : 'vendor/bin/jack breakpoint --limit ' . $maxOutdated
            )
            ->willReturn($this->processBreakpoint->reveal());
    }

    /**
     * @param bool $dev
     * @param string $maxOutdated
     *
     * @return void
     */
    private function configureUpgradeBuilders(bool $dev, string $maxOutdated): void
    {
        $composerUpdateWithDependenciesBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $composerUpdateAnsiBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $composerUpdateBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $depAnalyserBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $depAnalyserFinalBuilder = $this->prophesize(ProcessBuilderInterface::class);

        $this->processBuilder
            ->build($dev ? 'vendor/bin/jack raise-to-installed --dev' : 'vendor/bin/jack raise-to-installed')
            ->willReturn($this->processRaiseToInstalled->reveal());
        $this->processBuilder
            ->build($dev ? 'vendor/bin/jack open-versions --dev' : 'vendor/bin/jack open-versions')
            ->willReturn($this->processOpenVersions->reveal());
        $this->processBuilder->withArgument('-W')
            ->willReturn($composerUpdateWithDependenciesBuilder->reveal());
        $composerUpdateWithDependenciesBuilder->withArgument('--ansi')
            ->willReturn($composerUpdateAnsiBuilder->reveal());
        $composerUpdateAnsiBuilder->withArgument('--no-progress')
            ->willReturn($composerUpdateBuilder->reveal());
        $composerUpdateBuilder->build('composer update')
            ->willReturn($this->processComposerUpdate->reveal());
        $this->processBuilder->build('composer normalize')
            ->willReturn($this->processComposerNormalize->reveal());
        $this->processBuilder->withArgument('--config', '/app/composer-dependency-analyser.php')
            ->willReturn($depAnalyserBuilder->reveal());
        $depAnalyserBuilder->withArgument('--ignore-prod-only-in-dev-deps')
            ->willReturn($depAnalyserFinalBuilder->reveal());
        $depAnalyserFinalBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($this->processDepAnalyser->reveal());
        $this->processBuilder
            ->build(
                $dev ? 'vendor/bin/jack breakpoint --dev --limit ' . $maxOutdated : 'vendor/bin/jack breakpoint --limit ' . $maxOutdated
            )
            ->willReturn($this->processBreakpoint->reveal());
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
