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

use FastForward\DevTools\Console\Command\MetricsCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\Project\ProjectCapabilities;
use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use FastForward\DevTools\Environment\Environment as DevToolsEnvironment;
use FastForward\DevTools\Environment\RuntimeEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use FastForward\DevTools\Tests\Container\UsesContainerFactory;
use ReflectionMethod;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\putenv;

#[UsesClass(ContainerFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(DevToolsEnvironment::class)]
#[UsesClass(RuntimeEnvironment::class)]
#[CoversClass(MetricsCommand::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(ProjectCapabilities::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
#[UsesTrait(LogsCommandResults::class)]
final class MetricsCommandTest extends TestCase
{
    use ProphecyTrait;
    use UsesContainerFactory;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private MetricsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->setContainerEntry(LoggerInterface::class, $this->logger->reveal());
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->input->getOption('exclude')
            ->willReturn('vendor');
        $this->input->getOption('target')
            ->willReturn(ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS));
        $this->input->getOption('junit')
            ->willReturn(null);
        $this->input->getOption('progress')
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
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build(Argument::that(static fn(array $command): bool => \PHP_BINARY === $command[0]
            && str_starts_with((string) $command[1], '-derror_reporting=')
            && '-ddefault_socket_timeout=1' === $command[2]
            && DevToolsPathResolver::getPreferredToolBinaryPath('phpmetrics') === $command[3]))
            ->willReturn($this->process->reveal());
        $this->command = new MetricsCommand($this->processBuilder->reveal(), $this->processQueue->reveal());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->expectProcessQueued();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->log('info', 'Running code metrics analysis...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'Code metrics analysis completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->expectProcessQueued();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::FAILURE)
            ->shouldBeCalled();
        $this->logger->log('info', 'Running code metrics analysis...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Code metrics analysis failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(MetricsCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpMetricsInQuietModeWhenJsonIsRequested(): void
    {
        $this->expectProcessQueued();
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->processBuilder->withArgument('--quiet')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'Code metrics analysis completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillNotRunPhpMetricsInQuietModeWhenProgressIsRequested(): void
    {
        $this->expectProcessQueued();
        $this->input->getOption('progress')
            ->willReturn(true);
        $this->processBuilder->withArgument('--quiet')
            ->shouldNotBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalled();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillExcludeCustomRelativeWorkspaceByDefault(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=.artifacts');

        $command = new MetricsCommand($this->processBuilder->reveal(), $this->processQueue->reveal());

        self::assertSame(
            'vendor,tmp,cache,spec,build,.dev-tools,backup,resources,.artifacts',
            $command->getDefinition()
                ->getOption('exclude')
                ->getDefault()
        );
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
     * @return void
     */
    private function expectProcessQueued(): void
    {
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())
            ->shouldBeCalled();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunEvenWhenNoTestsOrPhpSourceExist(): void
    {
        $this->expectProcessQueued();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->log('info', 'Running code metrics analysis...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))->shouldBeCalled();
        $this->logger->log(
            'info',
            'Code metrics analysis completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }
}
