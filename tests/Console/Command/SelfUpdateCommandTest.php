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

use Prophecy\Argument;
use FastForward\DevTools\Console\Command\SelfUpdateCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Reflection\ClassReflection;
use FastForward\DevTools\SelfUpdate\SelfUpdateRunnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(SelfUpdateCommand::class)]
#[UsesClass(ClassReflection::class)]
#[UsesTrait(LogsCommandResults::class)]
final class SelfUpdateCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SelfUpdateRunnerInterface>
     */
    private ObjectProphecy $selfUpdateRunner;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private ObjectProphecy $logger;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private SelfUpdateCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->selfUpdateRunner = $this->prophesize(SelfUpdateRunnerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->logger->info(Argument::cetera())
            ->will(static function (): void {});
        $this->logger->log(Argument::cetera())
            ->will(static function (): void {});
        $this->logger->error(Argument::cetera())
            ->will(static function (): void {});
        $this->command = new SelfUpdateCommand($this->selfUpdateRunner->reveal(), $this->logger->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandNamesWillReturnAttributeNameAndAliases(): void
    {
        self::assertSame(
            ['dev-tools:self-update', 'self-update', 'selfupdate'],
            SelfUpdateCommand::getCommandNames()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillUpdateProjectInstallation(): void
    {
        $this->input->getOption('global')
            ->willReturn(false);
        $this->selfUpdateRunner->update(false, $this->output->reveal())
            ->willReturn(SelfUpdateCommand::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(SelfUpdateCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenUpdateFails(): void
    {
        $this->input->getOption('global')
            ->willReturn(true);
        $this->selfUpdateRunner->update(true, $this->output->reveal())
            ->willReturn(SelfUpdateCommand::FAILURE)
            ->shouldBeCalledOnce();

        self::assertSame(SelfUpdateCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $method = new ReflectionMethod($this->command, 'execute');

        return $method->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
