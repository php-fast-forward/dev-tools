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
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
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

use function Safe\unlink;
use function uniqid;

#[CoversClass(MetricsCommand::class)]
#[CoversClass(OutputFormat::class)]
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

    /**
     * @var ObjectProphecy<CommandResponderFactoryInterface>
     */
    private ObjectProphecy $commandResponderFactory;

    /**
     * @var ObjectProphecy<CommandResponderInterface>
     */
    private ObjectProphecy $commandResponder;

    private string $target;

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
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);
        $this->target = sys_get_temp_dir() . '/metrics-' . uniqid();

        foreach (['exclude', 'target', 'junit'] as $option) {
            $this->input->getOption($option)
                ->willReturn($this->commandDefaultOption($option));
        }

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build(
            [\PHP_BINARY, '-derror_reporting=' . (\E_ALL & ~\E_DEPRECATED), 'vendor/bin/phpmetrics']
        )
            ->willReturn($this->process->reveal());

        $this->command = new MetricsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->commandResponderFactory->reveal(),
        );
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->commandResponder->format()
            ->willReturn(OutputFormat::TEXT);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ([$this->target . '/report.json', $this->target . '/report-summary.json'] as $path) {
            if (file_exists($path)) {
                unlink($path);
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

        self::assertFalse($definition->hasOption('working-dir'));
        self::assertFalse($definition->hasOption('src'));
        self::assertTrue($definition->hasOption('exclude'));
        self::assertTrue($definition->hasOption('target'));
        self::assertFalse($definition->hasOption('report-html'));
        self::assertFalse($definition->hasOption('report-json'));
        self::assertFalse($definition->hasOption('report-summary-json'));
        self::assertTrue($definition->hasOption('junit'));
        self::assertFalse($definition->hasOption('cache-dir'));
        self::assertTrue($definition->hasOption('output-format'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunPhpMetrics(): void
    {
        $this->output->writeln('<info>Running code metrics analysis...</info>')
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--ansi')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--git', 'git')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(
            '--exclude',
            'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources'
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--target', null)
            ->shouldNotBeCalled();
        $this->processBuilder->withArgument('--report-html', $this->target)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-json', $this->target . '/report.json')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-summary-json', $this->target . '/report-summary.json')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--junit', null)
            ->shouldNotBeCalled();
        $this->processBuilder->withArgument('.')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code metrics analysis completed successfully.',
            [
                'command' => 'metrics',
                'exclude' => 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources',
                'target' => $this->target,
                'junit' => null,
                'process_output' => null,
            ],
        )->willReturn(MetricsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipUnsetOptionalReports(): void
    {
        $this->input->getOption('target')
            ->willReturn('.dev-tools/metrics/');
        $this->input->getOption('junit')
            ->willReturn(null);

        $this->processBuilder->withArgument('--report-html', '.dev-tools/metrics')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-json', '.dev-tools/metrics/report.json')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--report-summary-json', '.dev-tools/metrics/report-summary.json')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--junit', Argument::any())
            ->shouldNotBeCalled();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code metrics analysis completed successfully.',
            [
                'command' => 'metrics',
                'exclude' => 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources',
                'target' => '.dev-tools/metrics',
                'junit' => null,
                'process_output' => null,
            ],
        )->willReturn(MetricsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillIncludeJunitReportWhenRequested(): void
    {
        $this->input->getOption('junit')
            ->willReturn('.dev-tools/metrics/junit.xml');

        $this->processBuilder->withArgument('--junit', '.dev-tools/metrics/junit.xml')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code metrics analysis completed successfully.',
            [
                'command' => 'metrics',
                'exclude' => 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources',
                'target' => $this->target,
                'junit' => '.dev-tools/metrics/junit.xml',
                'process_output' => null,
            ],
        )->willReturn(MetricsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(MetricsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCapturePhpMetricsOutputWhenJsonOutputIsRequested(): void
    {
        $target = $this->target;

        $this->commandResponder->format()
            ->willReturn(OutputFormat::JSON);
        $this->output->writeln(Argument::cetera())
            ->shouldNotBeCalled();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(MetricsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code metrics analysis completed successfully.',
            Argument::that(static fn(array $context): bool => 'metrics' === $context['command']
                && 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources' === $context['exclude']
                && $target === $context['target']
                && null === $context['junit']
                && \is_string($context['process_output'])),
        )->willReturn(MetricsCommand::SUCCESS)->shouldBeCalledOnce();

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
            'exclude' => 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources',
            'target' => $this->target,
            'junit' => null,
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
