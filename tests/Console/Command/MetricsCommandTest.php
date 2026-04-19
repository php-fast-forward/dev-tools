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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Metrics\Report;
use FastForward\DevTools\Metrics\ReportLoaderInterface;
use FastForward\DevTools\Metrics\SummaryRendererInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(MetricsCommand::class)]
final class MetricsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<ReportLoaderInterface>
     */
    private ObjectProphecy $reportLoader;

    /**
     * @var ObjectProphecy<SummaryRendererInterface>
     */
    private ObjectProphecy $summaryRenderer;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $process;

    private MetricsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->reportLoader = $this->prophesize(ReportLoaderInterface::class);
        $this->summaryRenderer = $this->prophesize(SummaryRendererInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        foreach (['src', 'exclude', 'report-html', 'report-json', 'cache-dir'] as $option) {
            $this->input->getOption($option)
                ->willReturn($this->commandDefaultOption($option));
        }

        $this->filesystem->getAbsolutePath('vendor/bin/phpmetrics')
            ->willReturn('/app/vendor/bin/phpmetrics');
        $this->filesystem->exists('/app/vendor/bin/phpmetrics')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('src')
            ->willReturn('/app/src');
        $this->filesystem->exists('/app/src')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('tmp/cache/phpmetrics')
            ->willReturn('/app/tmp/cache/phpmetrics');
        $this->filesystem->getAbsolutePath('metrics.json', '/app/tmp/cache/phpmetrics')
            ->willReturn('/app/tmp/cache/phpmetrics/metrics.json');
        $this->filesystem->dirname('/app/tmp/cache/phpmetrics/metrics.json')
            ->willReturn('/app/tmp/cache/phpmetrics');

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build('vendor/bin/phpmetrics')
            ->willReturn($this->process->reveal());

        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS);

        $this->reportLoader->load('/app/tmp/cache/phpmetrics/metrics.json')
            ->willReturn(new Report(4.0, 75.0, 2, 1));
        $this->summaryRenderer->render(Argument::type(Report::class))
            ->willReturn(
                "<info>Metrics summary</info>\n"
                . "Average cyclomatic complexity by class: 4.00\n"
                . "Average maintainability index by class: 75.00\n"
                . "Classes analyzed: 2\n"
                . "Functions analyzed: 1"
            );

        $this->command = new MetricsCommand(
            $this->filesystem->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->reportLoader->reveal(),
            $this->summaryRenderer->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('metrics', $this->command->getName());
        self::assertSame('Analyzes code metrics with PhpMetrics.', $this->command->getDescription());
        self::assertSame(
            'This command runs PhpMetrics to analyze source code and prints a reduced summary.',
            $this->command->getHelp(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('src'));
        self::assertTrue($definition->hasOption('exclude'));
        self::assertTrue($definition->hasOption('report-html'));
        self::assertTrue($definition->hasOption('report-json'));
        self::assertTrue($definition->hasOption('cache-dir'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpMetricsAndPrintSummary(): void
    {
        $this->output->writeln('<info>Running code metrics analysis...</info>')
            ->shouldBeCalledOnce();
        $this->filesystem->mkdir('/app/tmp/cache/phpmetrics')
            ->shouldBeCalledTimes(2);
        $this->processBuilder->withArgument('--quiet')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--exclude', 'vendor,test,Test,tests,Tests,testing,Testing,bower_components,node_modules,cache,spec,build')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-json', '/app/tmp/cache/phpmetrics/metrics.json')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('/app/src')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->output->writeln(Argument::containingString('Metrics summary'))
            ->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenBinaryIsMissing(): void
    {
        $this->filesystem->exists('/app/vendor/bin/phpmetrics')
            ->willReturn(false);

        $this->output->writeln('<info>Running code metrics analysis...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln(Argument::containingString('PhpMetrics binary was not found'))
            ->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillIncludeHtmlReportWhenRequested(): void
    {
        $this->input->getOption('report-html')
            ->willReturn('build/metrics');
        $this->filesystem->getAbsolutePath('build/metrics')
            ->willReturn('/app/build/metrics');

        $this->filesystem->mkdir('/app/build/metrics')
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--report-html', '/app/build/metrics')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @param string $option the option name to resolve
     *
     * @return mixed the default option value used by the command
     */
    private function commandDefaultOption(string $option): mixed
    {
        return match ($option) {
            'src' => 'src',
            'exclude' => 'vendor,test,Test,tests,Tests,testing,Testing,bower_components,node_modules,cache,spec,build',
            'cache-dir' => 'tmp/cache/phpmetrics',
            default => null,
        };
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
