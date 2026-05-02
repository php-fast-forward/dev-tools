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

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Command\TestsCommand;
use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummary;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Project\ProjectCapabilities;
use FastForward\DevTools\Project\ProjectCapabilitiesResolverInterface;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
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
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(TestsCommand::class)]
#[UsesClass(BootstrapShimGenerator::class)]
#[UsesClass(CoverageSummary::class)]
#[UsesClass(ContainerFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(ProcessBuilder::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(ProjectCapabilities::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
#[UsesTrait(LogsCommandResults::class)]
final class TestsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $coverageSummaryLoader;

    private ObjectProphecy $composerJson;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $projectCapabilitiesResolver;

    private ObjectProphecy $runtimeEnvironment;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private TestsCommand $command;

    private string|false $agentEnvironment;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        ContainerFactory::reset();
        $this->agentEnvironment = getenv('AI_AGENT');
        $this->coverageSummaryLoader = $this->prophesize(CoverageSummaryLoaderInterface::class);
        $this->composerJson = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->projectCapabilitiesResolver = $this->prophesize(ProjectCapabilitiesResolverInterface::class);
        $this->runtimeEnvironment = $this->prophesize(RuntimeEnvironmentInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new TestsCommand(
            $this->coverageSummaryLoader->reveal(),
            $this->composerJson->reveal(),
            $this->filesystem->reveal(),
            new BootstrapShimGenerator($this->filesystem->reveal()),
            $this->fileLocator->reveal(),
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            $this->projectCapabilitiesResolver->reveal(),
        );

        $this->composerJson->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);
        $this->projectCapabilitiesResolver->resolve(Argument::any())
            ->willReturn(new ProjectCapabilities(
                [getcwd() . '/src'],
                'FastForward\\DevTools',
                false,
                true,
                false,
                true,
            ));
        $this->runtimeEnvironment->isAgentPresent()
            ->willReturn(false);
        $this->runtimeEnvironment->isComposerTestRun()
            ->willReturn(true);
        ContainerFactory::set(RuntimeEnvironmentInterface::class, $this->runtimeEnvironment->reveal());
        ContainerFactory::set(LoggerInterface::class, $this->logger->reveal());
        $this->fileLocator->locate(TestsCommand::CONFIG)->willReturn(getcwd() . '/' . TestsCommand::CONFIG);
        $this->filesystem->getAbsolutePath('./vendor/autoload.php')
            ->willReturn(getcwd() . '/vendor/autoload.php');
        $this->filesystem->getAbsolutePath(ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT))
            ->willReturn(getcwd() . '/.dev-tools/cache/phpunit');
        $this->filesystem->getAbsolutePath(ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE))
            ->willReturn(getcwd() . '/.dev-tools/coverage');
        $this->filesystem->getAbsolutePath('src/')
            ->willReturn(getcwd() . '/src');
        $this->filesystem->dumpFile(Argument::cetera())
            ->will(static function (): void {});

        foreach ($this->command->getDefinition()->getArguments() as $argument) {
            $this->input->getArgument($argument->getName())
                ->willReturn($argument->getDefault());
        }

        foreach ($this->command->getDefinition()->getOptions() as $option) {
            $this->input->getOption($option->getName())
                ->willReturn($option->getDefault());
        }

        $this->input->getOption('no-cache')
            ->willReturn(false);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        ContainerFactory::reset();

        if (false === $this->agentEnvironment) {
            putenv('AI_AGENT');

            return;
        }

        putenv('AI_AGENT=' . $this->agentEnvironment);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpUnitProcessWithConfigFile(): void
    {
        $generatedBootstrapPath = getcwd() . '/.dev-tools/cache/phpunit/bootstrap.php';

        $this->processQueue->add(
            Argument::that(static fn(Process $process): bool => str_contains(
                $process->getCommandLine(),
                '--configuration=' . getcwd() . '/' . TestsCommand::CONFIG,
            ) && str_contains(
                $process->getCommandLine(),
                '--bootstrap=' . $generatedBootstrapPath,
            ) && str_contains(
                $process->getCommandLine(),
                DevToolsPathResolver::getPreferredToolBinaryPath('phpunit'),
            ) && str_contains($process->getCommandLine(), '--cache-result') && str_contains(
                $process->getCommandLine(),
                '--cache-directory=' . getcwd() . '/.dev-tools/cache/phpunit',
            ) && str_contains($process->getCommandLine(), '--colors=always')),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithNoCacheWillDisablePhpUnitResultCache(): void
    {
        $this->input->getOption('no-cache')
            ->willReturn(true);

        $this->processQueue->add(
            Argument::that(static fn(Process $process): bool => str_contains(
                $process->getCommandLine(),
                '--do-not-cache-result',
            ) && ! str_contains($process->getCommandLine(), '--cache-directory=')),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillDisablePhpUnitProgressWhenJsonIsRequested(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);

        $this->processQueue->add(
            Argument::that(fn(Process $process): bool => $this->usesStructuredPhpUnitExecution($process)),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 0,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'success' === $context['output']['result']
                && 5 === $context['output']['summary']['assertions']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCaptureStructuredPhpUnitSummaryWhenAgentEnvironmentIsDetected(): void
    {
        $this->runtimeEnvironment->isAgentPresent()
            ->willReturn(true);
        $this->runtimeEnvironment->isComposerTestRun()
            ->willReturn(false);

        $this->processQueue->add(
            Argument::that(fn(Process $process): bool => $this->usesStructuredPhpUnitExecution($process)),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 0,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'success' === $context['output']['result']
                && 5 === $context['output']['summary']['assertions']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPreserveAnInheritedAgentEnvironmentWhenForcingStructuredPhpUnitOutput(): void
    {
        putenv('AI_AGENT=existing-agent');

        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);

        $this->processQueue->add(
            Argument::that(static fn(Process $process): bool => ! \array_key_exists('AI_AGENT', $process->getEnv())),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::type('array'),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillKeepPrettyJsonInsideTheStandardCommandLogOutput(): void
    {
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(true);

        $this->processQueue->add(
            Argument::that(fn(Process $process): bool => $this->usesStructuredPhpUnitExecution($process)),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 0,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'success' === $context['output']['result']
                && 5 === $context['output']['summary']['assertions']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCaptureStructuredPhpUnitSummaryAfterCoveragePreludeWhenAgentEnvironmentIsDetected(): void
    {
        $this->runtimeEnvironment->isAgentPresent()
            ->willReturn(true);
        $this->runtimeEnvironment->isComposerTestRun()
            ->willReturn(false);

        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "Generating code coverage report in PHP format ... done [00:00.002]\n\n{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 0,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'success' === $context['output']['result']
                && 5 === $context['output']['summary']['assertions']
                && "Generating code coverage report in PHP format ... done [00:00.002]" === $context['output']['raw_output']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillKeepTheExitCodeDerivedFailureResultAuthoritative(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);

        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 1,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::FAILURE;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->error(
            'PHPUnit tests failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'failure' === $context['output']['result']
                && 1 === $context['output']['summary']['failures']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillKeepRawPhpUnitOutputWhenStructuredSummaryCannotBeDecoded(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);

        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write("PHPUnit 12.5.24 by Sebastian Bergmann.\n\nOK (2 tests, 5 assertions)\n");

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'info',
            'PHPUnit tests completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'success' === $context['output']['result']
                && "PHPUnit 12.5.24 by Sebastian Bergmann.\n\nOK (2 tests, 5 assertions)" === $context['output']['raw_output']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillEnablePhpUnitProgressWhenRequested(): void
    {
        $this->input->getOption('progress')
            ->willReturn(true);

        $this->processQueue->add(
            Argument::that(static fn(Process $process): bool => ! str_contains(
                $process->getCommandLine(),
                '--no-progress',
            )),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInvalidMinCoverageWillReturnFailure(): void
    {
        $this->input->getOption('min-coverage')
            ->willReturn('invalid');
        $this->processQueue->run(Argument::cetera())->shouldNotBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'The --min-coverage option MUST be a numeric percentage.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWhenNoTestsDirectoryOrPhpSourceExists(): void
    {
        $this->projectCapabilitiesResolver->resolve(Argument::any())
            ->willReturn(new ProjectCapabilities([], null, false, false, false, false));
        $this->processQueue->add(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->run(Argument::cetera())->shouldNotBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))->shouldBeCalled();
        $this->logger->log(
            'warning',
            'Skipping PHPUnit tests because no tests directory or PHP source files were detected.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenCustomTestsPathDoesNotExist(): void
    {
        $this->input->getArgument('path')
            ->willReturn('missing-tests');
        $this->filesystem->getAbsolutePath('missing-tests')
            ->willReturn('/repo/missing-tests');
        $this->projectCapabilitiesResolver->resolve(Argument::any())
            ->willReturn(new ProjectCapabilities([], null, false, false, false, false));
        $this->processQueue->add(Argument::cetera())
            ->shouldNotBeCalled();
        $this->processQueue->run(Argument::cetera())
            ->shouldNotBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))->shouldBeCalled();
        $this->logger->error(
            'Tests path not found: {path}',
            Argument::that(static fn(array $context): bool => '/repo/missing-tests' === $context['path']
                && $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageBelowMinimumWillReturnFailure(): void
    {
        $coverageReportPath = getcwd() . '/.dev-tools/cache/phpunit/coverage.php';

        $this->input->getOption('min-coverage')
            ->willReturn('80');
        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(75, 100));
        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Minimum line coverage of 80.00% was not met. Current coverage: 75.00% (75/100 lines).',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && 75.0 === $context['line_coverage']
                && 75 === $context['covered_lines']
                && 100 === $context['total_lines']),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillEmitStructuredCoverageFailurePayloadWhenMinimumCoverageIsNotMet(): void
    {
        $coverageReportPath = getcwd() . '/.dev-tools/cache/phpunit/coverage.php';

        $this->runtimeEnvironment->isAgentPresent()
            ->willReturn(true);
        $this->runtimeEnvironment->isComposerTestRun()
            ->willReturn(false);
        $this->input->getOption('min-coverage')
            ->willReturn('80');
        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(75, 100));
        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->will(static function (array $arguments): int {
                $arguments[0]->write(
                    "{\n    \"result\": \"success\",\n    \"summary\": {\n        \"assertions\": 5,\n        \"failures\": 0,\n        \"tests\": 2,\n        \"warnings\": 0\n    }\n}\n"
                );

                return TestsCommand::SUCCESS;
            })->shouldBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(Argument::cetera())->shouldNotBeCalled();
        $this->logger->error(
            'Minimum line coverage of 80.00% was not met. Current coverage: 75.00% (75/100 lines).',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && isset($context['output'])
                && 'failure' === $context['output']['result']
                && 80.0 === (float) $context['output']['coverage']['minimum']
                && 75.0 === (float) $context['output']['coverage']['line_coverage']
                && 'Minimum line coverage of 80.00% was not met. Current coverage: 75.00% (75/100 lines).' === $context['output']['message']),
        )->shouldBeCalled();
        $this->output->writeln(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenCoverageSummaryCannotBeLoaded(): void
    {
        $coverageReportPath = getcwd() . '/.dev-tools/cache/phpunit/coverage.php';

        $this->input->getOption('min-coverage')
            ->willReturn('80');
        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willThrow(new RuntimeException('Coverage summary could not be loaded.'));
        $this->processQueue->add(
            Argument::type(Process::class),
            false,
            false,
            'Running PHPUnit Tests'
        )->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Coverage summary could not be loaded.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && null === $context['line_coverage']
                && null === $context['covered_lines']
                && null === $context['total_lines']),
        )->shouldBeCalled();

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }

    /**
     * @param Process $process
     *
     * @return bool
     */
    private function usesStructuredPhpUnitExecution(Process $process): bool
    {
        if (! str_contains($process->getCommandLine(), '--no-progress')) {
            return false;
        }

        if (str_contains($process->getCommandLine(), '--colors=always')) {
            return false;
        }

        $processEnvironment = $process->getEnv();

        if (\array_key_exists('AI_AGENT', $processEnvironment)) {
            return 'fast-forward/dev-tools' === $processEnvironment['AI_AGENT'];
        }

        return false !== getenv('AI_AGENT');
    }
}
