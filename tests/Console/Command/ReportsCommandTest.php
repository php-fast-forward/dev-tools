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

        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS);

        $this->command = new ReportsCommand($this->processBuilder->reveal(), $this->processQueue->reveal());
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
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunDocsAsDetachedAndTestsAndMetricsInSequence(): void
    {
        $this->output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>')
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

        $this->processQueue->add($this->docsProcess->reveal(), false, true)
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->testsProcess->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->metricsProcess->reveal())
            ->shouldBeCalledOnce();

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
