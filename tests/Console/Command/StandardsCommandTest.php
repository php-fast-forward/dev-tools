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

    private StandardsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->application = $this->prophesize(Application::class);
        $this->command = new StandardsCommand();
        $this->application->getHelperSet()
            ->willReturn(new HelperSet());
        $this->command->setApplication($this->application->reveal());

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
        $this->application->doRun(Argument::type(StringInput::class), $this->output->reveal())
            ->willReturn(StandardsCommand::SUCCESS)
            ->shouldBeCalledTimes(4);

        $this->output->writeln('<info>Running code standards checks...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>All code standards checks completed!</info>')
            ->shouldBeCalled();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
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

        self::assertSame(StandardsCommand::FAILURE, $this->invokeExecute());
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
