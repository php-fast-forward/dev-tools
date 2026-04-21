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
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(ReportsCommand::class)]
final class ReportsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private ReportsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->input->getOption('target')
            ->willReturn('.dev-tools');
        $this->input->getOption('coverage')
            ->willReturn('.dev-tools/coverage');
        $this->input->getOption('metrics')
            ->willReturn('.dev-tools/metrics');
        $this->input->getOption('output-format')
            ->willReturn('text');
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
        $this->processBuilder->build(Argument::any())->willReturn($this->process->reveal());

        $this->command = new ReportsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunReportsAndReturnSuccess(): void
    {
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ReportsCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->logger->info('Generating frontpage for Fast Forward documentation...')
            ->shouldBeCalled();
        $this->logger->info(
            'Documentation reports generated successfully.',
            [
                'command' => 'reports',
                'target' => '.dev-tools',
                'coverage' => '.dev-tools/coverage',
                'metrics' => '.dev-tools/metrics',
                'process_output' => null,
            ],
        )->shouldBeCalled();

        self::assertSame(ReportsCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCaptureBufferedOutputWhenJsonIsRequested(): void
    {
        $this->input->getOption('output-format')
            ->willReturn('json');
        $this->processQueue->add(Argument::type(Process::class), Argument::cetera())->shouldBeCalledTimes(3);
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(ReportsCommand::FAILURE)
            ->shouldBeCalledOnce();
        $this->logger->info('Generating frontpage for Fast Forward documentation...')
            ->shouldBeCalled();
        $this->logger->error(
            'Documentation reports generation failed.',
            Argument::that(static fn(array $context): bool => 'reports' === $context['command']
                && \is_string($context['process_output'])),
        )->shouldBeCalled();

        self::assertSame(ReportsCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
