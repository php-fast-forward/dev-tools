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

use function Safe\file_put_contents;
use function uniqid;

#[CoversClass(MetricsCommand::class)]
final class MetricsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

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

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $process;

    private string $jsonReport;

    private string $summaryReport;

    private MetricsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->jsonReport = sys_get_temp_dir() . '/metrics-' . uniqid() . '.json';
        $this->summaryReport = sys_get_temp_dir() . '/metrics-summary-' . uniqid() . '.json';
        $jsonReport = $this->jsonReport;
        $summaryReport = $this->summaryReport;

        foreach (['exclude', 'report-html', 'report-json', 'report-summary-json'] as $option) {
            $this->input->getOption($option)
                ->willReturn($this->commandDefaultOption($option));
        }

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build([\PHP_BINARY, '-derror_reporting=' . (\E_ALL & ~\E_DEPRECATED), 'vendor/bin/phpmetrics'])
            ->willReturn($this->process->reveal());

        $this->processQueue->run($this->output->reveal())
            ->will(static function () use ($summaryReport, $jsonReport): int {
                file_put_contents($summaryReport, <<<'JSON'
                    {"OOP":{"classes":2},"Complexity":{"avgCyclomaticComplexityByClass":4}}
                    JSON);
                file_put_contents($jsonReport, <<<'JSON'
                    {"App\\Foo":{"_type":"Hal\\Metric\\ClassMetric","mi":80,"methods":[{"_type":"Hal\\Metric\\FunctionMetric"},{"_type":"Hal\\Metric\\FunctionMetric"}]},"App\\Bar":{"_type":"Hal\\Metric\\ClassMetric","mi":70}}
                    JSON);

                return MetricsCommand::SUCCESS;
            });

        $this->command = new MetricsCommand($this->processBuilder->reveal(), $this->processQueue->reveal());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ([$this->jsonReport, $this->summaryReport] as $path) {
            if (file_exists($path)) {
                \unlink($path);
            }
        }
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
            'This command runs PhpMetrics to analyze the current working directory.',
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

        self::assertTrue($definition->hasOption('working-dir'));
        self::assertFalse($definition->hasOption('src'));
        self::assertTrue($definition->hasOption('exclude'));
        self::assertTrue($definition->hasOption('report-html'));
        self::assertTrue($definition->hasOption('report-json'));
        self::assertTrue($definition->hasOption('report-summary-json'));
        self::assertFalse($definition->hasOption('cache-dir'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpMetrics(): void
    {
        $this->output->writeln('<info>Running code metrics analysis...</info>')
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--ansi')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--git', 'git')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(
            '--exclude',
            'vendor,test,tests,tmp,cache,spec,build,backup,resources'
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-json', $this->jsonReport)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-summary-json', $this->summaryReport)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('.')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipUnsetOptionalReports(): void
    {
        $this->input->getOption('report-json')
            ->willReturn(null);
        $this->input->getOption('report-summary-json')
            ->willReturn(null);

        $this->processBuilder->withArgument('--report-json', Argument::any())
            ->shouldNotBeCalled();
        $this->processBuilder->withArgument('--report-summary-json', Argument::any())
            ->shouldNotBeCalled();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillIncludeHtmlReportWhenRequested(): void
    {
        $this->input->getOption('report-html')
            ->willReturn('build/metrics');

        $this->processBuilder->withArgument('--report-html', 'build/metrics')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

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
            'exclude' => 'vendor,test,tests,tmp,cache,spec,build,backup,resources',
            'report-json' => $this->jsonReport,
            'report-summary-json' => $this->summaryReport,
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
