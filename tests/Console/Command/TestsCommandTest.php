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
use FastForward\DevTools\Console\Command\TestsCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummary;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
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
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(TestsCommand::class)]
#[UsesClass(CoverageSummary::class)]
#[UsesClass(ProcessBuilder::class)]
final class TestsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CoverageSummaryLoaderInterface>
     */
    private ObjectProphecy $coverageSummaryLoader;

    /**
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composerJson;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private TestsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->coverageSummaryLoader = $this->prophesize(CoverageSummaryLoaderInterface::class);
        $this->composerJson = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new TestsCommand(
            $this->coverageSummaryLoader->reveal(),
            $this->composerJson->reveal(),
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            new ProcessBuilder(),
            $this->processQueue->reveal(),
        );

        $this->composerJson->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);

        $this->fileLocator->locate(TestsCommand::CONFIG)
            ->willReturn(getcwd() . '/' . TestsCommand::CONFIG);

        $this->filesystem->getAbsolutePath('./vendor/autoload.php')
            ->willReturn(getcwd() . '/vendor/autoload.php');
        $this->filesystem->getAbsolutePath('./tmp/cache/phpunit')
            ->willReturn(getcwd() . '/tmp/cache/phpunit');
        $this->filesystem->getAbsolutePath('.dev-tools/coverage')
            ->willReturn(getcwd() . '/.dev-tools/coverage');
        $this->filesystem->getAbsolutePath('src/')
            ->willReturn(getcwd() . '/src');

        foreach ($this->command->getDefinition()->getArguments() as $argument) {
            $this->input->getArgument($argument->getName())
                ->willReturn($argument->getDefault());
        }

        foreach ($this->command->getDefinition()->getOptions() as $option) {
            $this->input->getOption($option->getName())
                ->willReturn($option->getDefault());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('tests', $this->command->getName());
        self::assertSame('Runs PHPUnit tests.', $this->command->getDescription());
        self::assertSame('This command runs PHPUnit to execute your tests.', $this->command->getHelp());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOutputOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('no-progress'));
        self::assertTrue($definition->hasOption('coverage-summary'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpUnitProcessWithConfigFile(): void
    {
        $this->willQueueProcessMatching(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpunit')
                && str_contains($commandLine, '--configuration=' . getcwd() . '/' . TestsCommand::CONFIG);
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageWillIncludeCoverageArguments(): void
    {
        $this->willQueueProcessMatching(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-text')
                && ! str_contains($commandLine, '--only-summary-for-coverage-text')
                && str_contains($commandLine, '--coverage-html=' . getcwd() . '/.dev-tools/coverage');
        });

        $this->input->getOption('coverage')
            ->willReturn('.dev-tools/coverage');

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithNoProgressWillForwardNoProgressToPhpUnit(): void
    {
        $this->willQueueProcessMatching(static fn(Process $process): bool => str_contains(
            $process->getCommandLine(),
            '--no-progress',
        ));

        $this->input->getOption('no-progress')
            ->willReturn(true);

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageSummaryWillIncludeCoverageTextSummaryArgument(): void
    {
        $this->willQueueProcessMatching(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-text')
                && str_contains($commandLine, '--only-summary-for-coverage-text');
        });

        $this->input->getOption('coverage')
            ->willReturn('.dev-tools/coverage');
        $this->input->getOption('coverage-summary')
            ->willReturn(true);

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageSummaryWithoutCoverageWillNotGenerateCoverageTextSummary(): void
    {
        $this->willQueueProcessMatching(static function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return ! str_contains($commandLine, '--coverage-text')
                && ! str_contains($commandLine, '--only-summary-for-coverage-text');
        });

        $this->input->getOption('coverage-summary')
            ->willReturn(true);

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithMinCoverageWillGenerateCoveragePhpAndValidateIt(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->willQueueProcessMatching(function (Process $process) use ($coverageReportPath): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-php=' . $coverageReportPath)
                && ! str_contains($commandLine, '--coverage-html=');
        });

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(85, 100));

        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageAndMinCoverageWillValidateGeneratedCoverageReport(): void
    {
        $coverageReportPath = getcwd() . '/.dev-tools/coverage/coverage.php';

        $this->willQueueProcessMatching(function (Process $process) use ($coverageReportPath): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-html=' . getcwd() . '/.dev-tools/coverage')
                && str_contains($commandLine, '--coverage-php=' . $coverageReportPath);
        });

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(90, 100));

        $this->input->getOption('coverage')
            ->willReturn('.dev-tools/coverage');
        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageBelowMinimumWillReturnFailure(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->willQueueProcessMatching(static fn(Process $process): bool => str_contains(
            $process->getCommandLine(),
            '--coverage-php=' . $coverageReportPath,
        ));

        $this->output->writeln(Argument::type('string'))
            ->will(static function (): void {});
        $this->output->writeln(Argument::containingString('Minimum line coverage'))
            ->shouldBeCalled();

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(75, 100));

        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInvalidMinCoverageWillReturnFailure(): void
    {
        $this->output->writeln(Argument::type('string'))
            ->will(static function (): void {});
        $this->output->writeln(Argument::containingString('--min-coverage'))
            ->shouldBeCalled();

        $this->input->getOption('min-coverage')
            ->willReturn('abc');

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessQueueFails(): void
    {
        $this->willQueueProcessMatching(static fn(): bool => true, TestsCommand::FAILURE);

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @param callable $callback
     * @param int $result
     *
     * @return void
     */
    private function willQueueProcessMatching(callable $callback, int $result = TestsCommand::SUCCESS): void
    {
        $this->processQueue->add(Argument::that($callback))
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn($result)
            ->shouldBeCalledOnce();
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
