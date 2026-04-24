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
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
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
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(DependenciesCommand::class)]
#[UsesClass(ProcessBuilder::class)]
#[UsesTrait(LogsCommandResults::class)]
final class DependenciesCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $logger;

    private DependenciesCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->fileLocator->locate('composer-dependency-analyser.php')
            ->willReturn('/app/composer-dependency-analyser.php');
        $this->input->getOption('max-outdated')
            ->willReturn('5');
        $this->input->getOption('dev')
            ->willReturn(false);
        $this->input->getOption('upgrade')
            ->willReturn(false);
        $this->input->getOption('dump-usage')
            ->willReturn(null);
        $this->input->getOption('show-shadow-dependencies')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->getFormatter()
            ->willReturn(new OutputFormatter());

        $this->command = new DependenciesCommand(
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            $this->fileLocator->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenPreviewAndAnalyzersSucceed(): void
    {
        $this->processQueue->add(Argument::type(Process::class))->shouldBeCalledTimes(3);
        $this->processQueue->add(Argument::type(Process::class), false)->shouldBeCalledOnce();
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(DependenciesCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Running dependency analysis...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalledOnce();
        $this->logger->log(
            'info',
            'Dependency analysis completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalledOnce();

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
        $this->processQueue->run(Argument::cetera())->shouldNotBeCalled();
        $this->logger->error(
            'The --max-outdated option MUST be a numeric threshold.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface),
        )->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillIgnoreJackFailuresWhenMaxOutdatedIsDisabled(): void
    {
        $this->input->getOption('max-outdated')
            ->willReturn('-1');
        $this->processQueue->add(Argument::type(Process::class))->shouldBeCalledTimes(3);
        $this->processQueue->add(Argument::type(Process::class), true)->shouldBeCalledOnce();
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(DependenciesCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Running dependency analysis...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalledOnce();
        $this->logger->log(
            'info',
            'Dependency analysis completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalledOnce();

        self::assertSame(DependenciesCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function composerDependencyAnalyserProcessWillHideShadowDependenciesByDefault(): void
    {
        $this->input->getOption('show-shadow-dependencies')
            ->willReturn(false);

        $this->assertComposerDependencyAnalyserEnvironment('0');
    }

    /**
     * @return void
     */
    #[Test]
    public function composerDependencyAnalyserProcessCanReportShadowDependencies(): void
    {
        $this->input->getOption('show-shadow-dependencies')
            ->willReturn(true);

        $this->assertComposerDependencyAnalyserEnvironment('1');
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }

    /**
     * @param string $expectedValue
     *
     * @return void
     */
    private function assertComposerDependencyAnalyserEnvironment(string $expectedValue): void
    {
        $processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $configuredProcessBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $process = $this->prophesize(Process::class);
        $command = new DependenciesCommand(
            $processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->fileLocator->reveal(),
            $this->logger->reveal(),
        );

        $processBuilder->withArgument('--config', '/app/composer-dependency-analyser.php')
            ->willReturn($configuredProcessBuilder->reveal())
            ->shouldBeCalledOnce();
        $configuredProcessBuilder->build('vendor/bin/composer-dependency-analyser')
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();
        $process->setEnv([
            ComposerDependencyAnalyserConfig::ENV_SHOW_SHADOW_DEPENDENCIES => $expectedValue,
        ])->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        (new ReflectionMethod($command, 'getComposerDependencyAnalyserCommand'))
            ->invoke($command, $this->input->reveal());
    }
}
