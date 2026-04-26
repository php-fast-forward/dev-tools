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

namespace FastForward\DevTools\Tests\Composer\Capability;

use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Composer\Command\ProxyCommand;
use FastForward\DevTools\Console\Command\FixtureWithoutAsCommand;
use FastForward\DevTools\Console\DevTools;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

#[CoversClass(DevToolsCommandProvider::class)]
#[UsesClass(DevTools::class)]
#[UsesClass(ProxyCommand::class)]
final class DevToolsCommandProviderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $container;

    private ObjectProphecy $devTools;

    private DevToolsCommandProvider $commandProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->devTools = $this->prophesize(DevTools::class);

        $this->container->get(DevTools::class)
            ->willReturn($this->devTools->reveal())
            ->shouldBeCalledOnce();

        $this->devTools->all()
            ->willReturn([])->shouldBeCalledOnce();

        $this->commandProvider = new DevToolsCommandProvider();

        $property = new ReflectionProperty(DevTools::class, 'container');
        $property->setValue(null, $this->container->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnEmptyArrayWhenNoCommandsAreRegistered(): void
    {
        $commands = $this->commandProvider->getCommands();

        self::assertIsArray($commands);
        self::assertEmpty($commands);
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnComposerProxyCommandsForRegisteredSymfonyCommands(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('agents');
        $symfonyCommand->setAliases([]);
        $symfonyCommand->setDescription('Synchronize agents.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->devTools->all()
            ->willReturn([
                'agents' => $symfonyCommand,
            ])
            ->shouldBeCalledOnce();

        $commands = array_values($this->commandProvider->getCommands());
        $command = $commands[0];

        self::assertIsArray($commands);
        self::assertCount(1, $commands);
        self::assertInstanceOf(ProxyCommand::class, $command);
        self::assertSame('agents', $command->getName());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillIgnoreAliasEntriesFromApplicationAllRegistry(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('reports:tests');
        $symfonyCommand->setAliases(['tests', 'phpunit']);
        $symfonyCommand->setDescription('Runs PHPUnit tests.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->devTools->all()
            ->willReturn([
                'reports:tests' => $symfonyCommand,
                'tests' => $symfonyCommand,
            ])
            ->shouldBeCalledOnce();

        $commands = array_values($this->commandProvider->getCommands());
        $proxyCommand = $commands[0];

        self::assertCount(1, $commands);
        self::assertInstanceOf(ProxyCommand::class, $proxyCommand);
        self::assertSame('reports:tests', $proxyCommand->getName());
        self::assertSame(['tests', 'phpunit'], $proxyCommand->getAliases());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillPreserveAliasDefinitionsInProxyCommand(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('dev-tools:standards');
        $symfonyCommand->setAliases(['standards']);
        $symfonyCommand->setDescription('Runs standards checks.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->devTools->all()
            ->willReturn([
                'dev-tools:standards' => $symfonyCommand,
                'standards' => $symfonyCommand,
            ])
            ->shouldBeCalledOnce();

        $proxyCommand = array_values($this->commandProvider->getCommands())[0];

        self::assertInstanceOf(ProxyCommand::class, $proxyCommand);
        self::assertSame('dev-tools:standards', $proxyCommand->getName());
        self::assertSame(['standards'], $proxyCommand->getAliases());
    }
}
