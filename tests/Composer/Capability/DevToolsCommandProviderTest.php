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

use Composer\Command\BaseCommand;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Console\Command\ProxyCommand;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Console\DevToolsComposer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;

#[CoversClass(DevToolsCommandProvider::class)]
#[UsesClass(DevTools::class)]
final class DevToolsCommandProviderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $container;

    private ObjectProphecy $devTools;

    private ObjectProphecy $devToolsComposer;

    private DevToolsCommandProvider $commandProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->devTools = $this->prophesize(DevTools::class);
        $this->devToolsComposer = $this->prophesize(DevToolsComposer::class);

        $this->container->get(DevTools::class)
            ->willReturn($this->devTools->reveal())
            ->shouldBeCalledOnce();
        $this->container->get(DevToolsComposer::class)
            ->willReturn($this->devToolsComposer->reveal())
            ->shouldBeCalledOnce();

        $this->devTools->all()
            ->willReturn([])->shouldBeCalledOnce();
        $this->devToolsComposer->all()
            ->willReturn([])->shouldBeCalledOnce();

        $this->commandProvider = new DevToolsCommandProvider();

        $property = new ReflectionProperty(DevTools::class, 'container');
        $property->setValue(null, $this->container->reveal());
        $propertyComposer = new ReflectionProperty(DevToolsComposer::class, 'container');
        $propertyComposer->setValue(null, $this->container->reveal());
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
    public function getCommandsWillReturnRegisteredBaseCommands(): void
    {
        $composerCommand = new class extends BaseCommand {
            public function __construct()
            {
                parent::__construct('legacy');
            }

            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        };
        $symfonyCommand = new class extends Command {
            public function __construct()
            {
                parent::__construct('agents');
            }

            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        };

        $this->devTools->all()
            ->willReturn([$symfonyCommand])->shouldBeCalledOnce();
        $this->devToolsComposer->all()
            ->willReturn([$composerCommand])->shouldBeCalledOnce();

        $commands = $this->commandProvider->getCommands();

        self::assertIsArray($commands);
        self::assertCount(2, $commands);
        self::assertSame($composerCommand, $commands[0]);
        self::assertInstanceOf(ProxyCommand::class, $commands[1]);
        self::assertSame('agents', $commands[1]->getName());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillSkipLegacyReservedSymfonyAliases(): void
    {
        $legacyCommand = new class extends BaseCommand {
            public function __construct()
            {
                parent::__construct('migrated');
            }

            public function getAliases(): array
            {
                return ['agents-alias'];
            }

            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        };
        $symfonyCommand = new class extends Command {
            public function __construct()
            {
                parent::__construct('migrated');
            }

            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        };

        $this->devTools->all()
            ->willReturn([$symfonyCommand])->shouldBeCalledOnce();
        $this->devToolsComposer->all()
            ->willReturn([$legacyCommand])->shouldBeCalledOnce();

        $commands = $this->commandProvider->getCommands();

        self::assertCount(1, $commands);
        self::assertSame($legacyCommand, $commands[0]);
    }
}
