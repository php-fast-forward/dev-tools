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

use FastForward\DevTools\Console\Command\ReportsCommand;
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

#[CoversClass(ReportsCommand::class)]
#[CoversClass(OutputFormat::class)]
final class ReportsCommandTest extends TestCase
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
    private ObjectProphecy $docsProcess;

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $testsProcess;

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $metricsProcess;

    /**
     * @var ObjectProphecy<CommandResponderFactoryInterface>
     */
    private ObjectProphecy $commandResponderFactory;

    /**
     * @var ObjectProphecy<CommandResponderInterface>
     */
    private ObjectProphecy $commandResponder;

    private ReportsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->docsProcess = $this->prophesize(Process::class);
        $this->testsProcess = $this->prophesize(Process::class);
        $this->metricsProcess = $this->prophesize(Process::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);

        $this->input->getOption('target')
            ->willReturn('.dev-tools');
        $this->input->getOption('coverage')
            ->willReturn('.dev-tools/coverage');
        $this->input->getOption('metrics')
            ->willReturn('.dev-tools/metrics');

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build('composer dev-tools docs --')
            ->willReturn($this->docsProcess->reveal());

        $this->processBuilder->build('composer dev-tools tests --')
            ->willReturn($this->testsProcess->reveal());
        $this->processBuilder->build('composer dev-tools metrics --')
            ->willReturn($this->metricsProcess->reveal());

        $this->command = new ReportsCommand(
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
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('reports', $this->command->getName());
        self::assertSame('Generates the frontpage for Fast Forward documentation.', $this->command->getDescription());
        self::assertSame(
            'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('target'));
        self::assertTrue($definition->hasOption('coverage'));
        self::assertTrue($definition->hasOption('metrics'));
        self::assertTrue($definition->hasOption('output-format'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunDocsAsDetachedAndTestsAndMetricsInSequence(): void
    {
        $this->output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>')
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        $this->processBuilder->withArgument('--ansi')
            ->shouldBeCalled()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--target', '.dev-tools')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--coverage', '.dev-tools/coverage')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--no-progress')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--coverage-summary')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--target', '.dev-tools/metrics')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--junit', '.dev-tools/coverage/junit.xml')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processQueue->add($this->docsProcess->reveal(), false, true)
            ->shouldBeCalledOnce();

        $this->processQueue->add($this->testsProcess->reveal())
            ->shouldBeCalledOnce();

        $this->processQueue->add($this->metricsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Documentation reports generated successfully.',
            [
                'command' => 'reports',
                'target' => '.dev-tools',
                'coverage' => '.dev-tools/coverage',
                'metrics' => '.dev-tools/metrics',
                'process_output' => null,
            ],
        )->willReturn(ReportsCommand::SUCCESS)->shouldBeCalledOnce();

        $result = $this->executeCommand();

        self::assertSame(ReportsCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunMetricsCommandWhenRequested(): void
    {
        $this->input->getOption('metrics')
            ->willReturn('tmp/metrics');

        $this->processBuilder->withArgument('--target', 'tmp/metrics')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--junit', '.dev-tools/coverage/junit.xml')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();

        $this->processQueue->add($this->docsProcess->reveal(), false, true)
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->testsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->metricsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Documentation reports generated successfully.',
            [
                'command' => 'reports',
                'target' => '.dev-tools',
                'coverage' => '.dev-tools/coverage',
                'metrics' => 'tmp/metrics',
                'process_output' => null,
            ],
        )->willReturn(ReportsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPropagateJsonOutputFormatToSubCommands(): void
    {
        $this->commandResponder->format()
            ->willReturn(OutputFormat::JSON);
        $this->output->writeln(Argument::cetera())
            ->shouldNotBeCalled();
        $this->processBuilder->withArgument('--output-format', 'json')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->add($this->docsProcess->reveal(), false, true)
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->testsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->metricsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Documentation reports generated successfully.',
            Argument::that(static fn(array $context): bool => 'reports' === $context['command']
                && '.dev-tools' === $context['target']
                && '.dev-tools/coverage' === $context['coverage']
                && '.dev-tools/metrics' === $context['metrics']
                && \is_string($context['process_output'])),
        )->willReturn(ReportsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
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
