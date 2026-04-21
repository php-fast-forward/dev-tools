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

use Composer\Console\Application;
use FastForward\DevTools\Console\Command\StandardsCommand;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\CommandResponderInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(StandardsCommand::class)]
#[CoversClass(OutputFormat::class)]
final class StandardsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<Application>
     */
    private ObjectProphecy $application;

    /**
     * @var ObjectProphecy<CommandResponderFactoryInterface>
     */
    private ObjectProphecy $commandResponderFactory;

    /**
     * @var ObjectProphecy<CommandResponderInterface>
     */
    private ObjectProphecy $commandResponder;

    private StandardsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->application = $this->prophesize(Application::class);
        $this->commandResponderFactory = $this->prophesize(CommandResponderFactoryInterface::class);
        $this->commandResponder = $this->prophesize(CommandResponderInterface::class);
        $this->command = new StandardsCommand($this->commandResponderFactory->reveal());
        $this->application->getHelperSet()
            ->willReturn(new HelperSet());
        $this->command->setApplication($this->application->reveal());
        $this->commandResponderFactory->from($this->input->reveal(), $this->output->reveal())
            ->willReturn($this->commandResponder->reveal());
        $this->commandResponder->format()
            ->willReturn(OutputFormat::TEXT);

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
        self::assertSame('standards', $this->command->getName());
        self::assertSame('Runs Fast Forward code standards checks.', $this->command->getDescription());
        self::assertSame(
            'This command runs all Fast Forward code standards checks, including code refactoring, PHPDoc validation, code style checks, documentation generation, and tests execution.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunSuiteSequentially(): void
    {
        $commands = [];

        $this->application->doRun(Argument::type(StringInput::class), $this->output->reveal())
            ->will(function (array $arguments) use (&$commands): int {
                /** @var StringInput $input */
                $input = $arguments[0];
                $commands[] = (string) $input;

                return StandardsCommand::SUCCESS;
            })
            ->shouldBeCalledTimes(4);

        $this->output->writeln('<info>Running code standards checks...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>All code standards checks completed!</info>')
            ->shouldBeCalled();
        $this->commandResponder->success(
            'Code standards checks completed successfully.',
            [
                'command' => 'standards',
                'fix' => false,
                'commands' => ['refactor', 'phpdoc', 'code-style', 'reports'],
                'process_output' => null,
            ],
        )->willReturn(StandardsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
        self::assertSame(['refactor', 'phpdoc', 'code-style', 'reports'], $commands);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenAnyCommandFails(): void
    {
        $this->application->doRun(Argument::type(StringInput::class), $this->output->reveal())
            ->willReturn(
                StandardsCommand::SUCCESS,
                StandardsCommand::FAILURE,
                StandardsCommand::SUCCESS,
                StandardsCommand::SUCCESS,
            )
            ->shouldBeCalledTimes(4);
        $this->commandResponder->failure(
            'Code standards checks failed.',
            [
                'command' => 'standards',
                'fix' => false,
                'commands' => ['refactor', 'phpdoc', 'code-style', 'reports'],
                'process_output' => null,
            ],
        )->willReturn(StandardsCommand::FAILURE)->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillNotPassFixToReportsCommand(): void
    {
        $commands = [];

        $this->input->getOption('fix')
            ->willReturn(true);
        $this->application->doRun(Argument::type(StringInput::class), $this->output->reveal())
            ->will(function (array $arguments) use (&$commands): int {
                /** @var StringInput $input */
                $input = $arguments[0];
                $commands[] = (string) $input;

                return StandardsCommand::SUCCESS;
            })
            ->shouldBeCalledTimes(4);
        $this->commandResponder->success(
            'Code standards checks completed successfully.',
            [
                'command' => 'standards',
                'fix' => true,
                'commands' => ['refactor', 'phpdoc', 'code-style', 'reports'],
                'process_output' => null,
            ],
        )->willReturn(StandardsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
        self::assertSame(['refactor --fix', 'phpdoc --fix', 'code-style --fix', 'reports'], $commands);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPropagateJsonOutputFormatToSubCommands(): void
    {
        $commands = [];

        $this->commandResponder->format()
            ->willReturn(OutputFormat::JSON);
        $this->output->writeln(Argument::cetera())
            ->shouldNotBeCalled();
        $this->application->doRun(Argument::type(StringInput::class), Argument::type('object'))
            ->will(function (array $arguments) use (&$commands): int {
                /** @var StringInput $input */
                $input = $arguments[0];
                $commands[] = (string) $input;

                return StandardsCommand::SUCCESS;
            })
            ->shouldBeCalledTimes(4);
        $this->commandResponder->success(
            'Code standards checks completed successfully.',
            Argument::that(static fn(array $context): bool => 'standards' === $context['command']
                && false === $context['fix']
                && ['refactor', 'phpdoc', 'code-style', 'reports'] === $context['commands']
                && \is_string($context['process_output'])),
        )->willReturn(StandardsCommand::SUCCESS)->shouldBeCalledOnce();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
        self::assertSame(
            [
                'refactor --output-format=json',
                'phpdoc --output-format=json',
                'code-style --output-format=json',
                'reports --output-format=json',
            ],
            $commands,
        );
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
