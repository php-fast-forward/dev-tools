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

use FastForward\DevTools\Console\Command\RefactorCommand;
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
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(RefactorCommand::class)]
#[CoversClass(OutputFormat::class)]
final class RefactorCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

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

    private RefactorCommand $command;

    private const string CONFIG_PATH = '/path/to/rector.php';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);

        $this->fileLocator->locate(RefactorCommand::CONFIG)
            ->willReturn(self::CONFIG_PATH);

        $this->input->getOption('fix')
            ->willReturn(false);

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build('vendor/bin/rector')
            ->willReturn($this->process->reveal());

        $this->command = new RefactorCommand(
            $this->fileLocator->reveal(),
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
        self::assertSame('refactor', $this->command->getName());
        self::assertSame('Runs Rector for code refactoring.', $this->command->getDescription());
        self::assertSame('This command runs Rector to refactor your code.', $this->command->getHelp());
        self::assertSame(['rector'], $this->command->getAliases());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('fix'));
        self::assertTrue($definition->hasOption('config'));
        self::assertTrue($definition->hasOption('output-format'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunRectorProcessWithDryRunWhenFixIsFalse(): void
    {
        $this->processBuilder->withArgument('process')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--config')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument(self::CONFIG_PATH)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--dry-run')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Running Rector for code refactoring...</info>')
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code refactoring checks completed successfully.',
            [
                'command' => 'refactor',
                'fix' => false,
                'config' => RefactorCommand::CONFIG,
                'process_output' => null,
            ],
        )->willReturn(RefactorCommand::SUCCESS)->shouldBeCalledOnce();

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        $result = $this->executeCommand();

        self::assertSame(RefactorCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunRectorProcessWithoutDryRunWhenFixIsTrue(): void
    {
        $this->input->getOption('fix')
            ->willReturn(true);

        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Running Rector for code refactoring...</info>')
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--dry-run')
            ->shouldNotBeCalled();
        $this->commandResponder->success(
            'Code refactoring checks completed successfully.',
            [
                'command' => 'refactor',
                'fix' => true,
                'config' => RefactorCommand::CONFIG,
                'process_output' => null,
            ],
        )->willReturn(RefactorCommand::SUCCESS)->shouldBeCalledOnce();

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        $result = $this->executeCommand();

        self::assertSame(RefactorCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCaptureProcessOutputWhenJsonOutputIsRequested(): void
    {
        $this->commandResponder->format()
            ->willReturn(OutputFormat::JSON);
        $this->output->writeln(Argument::cetera())
            ->shouldNotBeCalled();
        $this->processQueue->run(Argument::type('object'))
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalledOnce();
        $this->commandResponder->success(
            'Code refactoring checks completed successfully.',
            Argument::that(static fn(array $context): bool => 'refactor' === $context['command']
                && false === $context['fix']
                && RefactorCommand::CONFIG === $context['config']
                && \is_string($context['process_output'])),
        )->willReturn(RefactorCommand::SUCCESS)->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        self::assertSame(RefactorCommand::SUCCESS, $this->executeCommand());
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
