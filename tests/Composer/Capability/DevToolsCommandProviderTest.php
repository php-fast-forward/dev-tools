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
use FastForward\DevTools\Composer\DevToolsPluginInterface;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Console\Command\FixtureWithoutAsCommand;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use FastForward\DevTools\Path\DevToolsPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[UsesClass(GithubActionOutput::class)]
#[CoversClass(DevToolsCommandProvider::class)]
#[UsesClass(ContainerFactory::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(ProxyCommand::class)]
final class DevToolsCommandProviderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $plugin;

    /**
     * @var ObjectProphecy<DevTools>
     */
    private ObjectProphecy $devTools;

    /**
     * @var array<string, FixtureWithoutAsCommand>
     */
    private array $applicationCommands = [];

    private DevToolsCommandProvider $commandProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        ContainerFactory::reset();
        $this->plugin = $this->prophesize(DevToolsPluginInterface::class);
        $this->devTools = $this->prophesize(DevTools::class);

        $this->plugin->isRegisteredCommand(null)
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('agents')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('reports:tests')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('tests')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('phpunit')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('dev-tools:standards')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('standards')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('dev-tools:self-update')
            ->willReturn(false);
        $this->plugin->isRegisteredCommand('self-update')
            ->willReturn(true);
        $this->plugin->isRegisteredCommand('install')
            ->willReturn(true);

        $this->commandProvider = new DevToolsCommandProvider([
            'plugin' => $this->plugin->reveal(),
        ]);

        $testCase = $this;
        $this->devTools->all()
            ->will(static fn(): array => $testCase->applicationCommands);
        ContainerFactory::set(DevTools::class, $this->devTools->reveal());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        ContainerFactory::reset();
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

        $this->applicationCommands = [
            'agents' => $symfonyCommand,
        ];

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

        $this->applicationCommands = [
            'reports:tests' => $symfonyCommand,
            'tests' => $symfonyCommand,
        ];

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
    public function getCommandsWillPreserveSafeAliasesThroughComposerPlugin(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('dev-tools:standards');
        $symfonyCommand->setAliases(['standards']);
        $symfonyCommand->setDescription('Runs standards checks.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->applicationCommands = [
            'dev-tools:standards' => $symfonyCommand,
            'standards' => $symfonyCommand,
        ];

        $proxyCommand = array_values($this->commandProvider->getCommands())[0];

        self::assertInstanceOf(ProxyCommand::class, $proxyCommand);
        self::assertSame('dev-tools:standards', $proxyCommand->getName());
        self::assertSame(['standards'], $proxyCommand->getAliases());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillNotExposeSelfUpdateAliasToComposer(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('dev-tools:self-update');
        $symfonyCommand->setAliases(['self-update']);
        $symfonyCommand->setDescription('Updates DevTools.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->applicationCommands = [
            'dev-tools:self-update' => $symfonyCommand,
            'self-update' => $symfonyCommand,
        ];

        $proxyCommand = array_values($this->commandProvider->getCommands())[0];

        self::assertInstanceOf(ProxyCommand::class, $proxyCommand);
        self::assertSame('dev-tools:self-update', $proxyCommand->getName());
        self::assertSame([], $proxyCommand->getAliases());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillNotExposeCommandsOwnedByComposer(): void
    {
        $symfonyCommand = new FixtureWithoutAsCommand('install');
        $symfonyCommand->setAliases([]);
        $symfonyCommand->setDescription('Conflicting command.');
        $symfonyCommand->setHelp('');
        $symfonyCommand->setHidden(false);

        $this->applicationCommands = [
            'install' => $symfonyCommand,
        ];

        self::assertSame([], $this->commandProvider->getCommands());
    }
}
