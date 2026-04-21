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
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(StandardsCommand::class)]
final class StandardsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $logger;

    private ObjectProphecy $application;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private StandardsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->application = $this->prophesize(Application::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->application->getHelperSet()
            ->willReturn(new HelperSet());

        $this->command = new StandardsCommand($this->logger->reveal());
        $this->command->setApplication($this->application->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunSuiteSequentially(): void
    {
        $commands = [];

        $this->application->doRun(Argument::type('object'), $this->output->reveal())
            ->will(function (array $arguments) use (&$commands): int {
                $commands[] = (string) $arguments[0];

                return StandardsCommand::SUCCESS;
            })->shouldBeCalledTimes(4);
        $this->logger->info('Running code standards checks...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->info(
            'Code standards checks completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && ['refactor', 'phpdoc', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
        self::assertSame(['refactor', 'phpdoc', 'code-style', 'reports'], array_map(trim(...), $commands));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenAnyCommandFails(): void
    {
        $calls = 0;

        $this->application->doRun(Argument::type('object'), $this->output->reveal())
            ->will(function () use (&$calls): int {
                ++$calls;

                return 2 === $calls ? StandardsCommand::FAILURE : StandardsCommand::SUCCESS;
            })->shouldBeCalledTimes(4);
        $this->logger->info('Running code standards checks...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Code standards checks failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface
                && ['refactor', 'phpdoc', 'code-style', 'reports'] === $context['commands']),
        )->shouldBeCalled();

        self::assertSame(StandardsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
