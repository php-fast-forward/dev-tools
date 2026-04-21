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
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(RefactorCommand::class)]
#[UsesTrait(LogsCommandResults::class)]
final class RefactorCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $process;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $logger;

    private RefactorCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('no-progress')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->getFormatter()
            ->willReturn(new OutputFormatter());
        $this->fileLocator->locate(RefactorCommand::CONFIG)->willReturn('/path/to/rector.php');

        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build(Argument::any())->willReturn($this->process->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();

        $this->command = new RefactorCommand(
            $this->fileLocator->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->info('Running Rector for code refactoring...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'Code refactoring checks completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(RefactorCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::FAILURE)
            ->shouldBeCalled();
        $this->logger->info('Running Rector for code refactoring...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Code refactoring checks failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(RefactorCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRequestJsonOutputAndDisableProgressWhenJsonIsRequested(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->processBuilder->withArgument('--no-progress-bar')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processBuilder->withArgument('--output-format', 'json')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalled();

        self::assertSame(RefactorCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillDisableProgressWhenRequested(): void
    {
        $this->input->getOption('no-progress')
            ->willReturn(true);
        $this->processBuilder->withArgument('--no-progress-bar')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::SUCCESS)
            ->shouldBeCalled();

        self::assertSame(RefactorCommand::SUCCESS, $this->executeCommand());
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
