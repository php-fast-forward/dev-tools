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
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
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

    private ObjectProphecy $coverageSummaryLoader;

    private ObjectProphecy $composerJson;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

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
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new TestsCommand(
            $this->coverageSummaryLoader->reveal(),
            $this->composerJson->reveal(),
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );

        $this->composerJson->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);
        $this->fileLocator->locate(TestsCommand::CONFIG)->willReturn(getcwd() . '/' . TestsCommand::CONFIG);
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
    public function executeWillRunPhpUnitProcessWithConfigFile(): void
    {
        $this->processQueue->add(Argument::that(static fn(Process $process): bool => str_contains(
            $process->getCommandLine(),
            '--configuration=' . getcwd() . '/' . TestsCommand::CONFIG,
        )))->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(TestsCommand::SUCCESS)->shouldBeCalled();
        $this->logger->info('Running PHPUnit tests...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->info(
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
    public function executeWithCoverageBelowMinimumWillReturnFailure(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->input->getOption('min-coverage')
            ->willReturn('80');
        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(75, 100));
        $this->processQueue->add(Argument::type(Process::class))->shouldBeCalled();
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
    public function executeWillReturnFailureWhenCoverageSummaryCannotBeLoaded(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->input->getOption('min-coverage')
            ->willReturn('80');
        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willThrow(new RuntimeException('Coverage summary could not be loaded.'));
        $this->processQueue->add(Argument::type(Process::class))->shouldBeCalled();
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
}
