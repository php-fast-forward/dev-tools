<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Composer\Capability;

use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Console\Command\CodeStyleCommand;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Psr\Container\Container as StaticContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(DevToolsCommandProvider::class)]
#[UsesClass(CodeStyleCommand::class)]
#[UsesClass(DevTools::class)]
#[UsesClass(StaticContainer::class)]
final class DevToolsCommandProviderTest extends TestCase
{
    use ProphecyTrait;

    private DevToolsCommandProvider $commandProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->commandProvider = new DevToolsCommandProvider();
        $this->setStaticContainer(null);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->setStaticContainer(null);
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnCommandsFromConfiguredDevToolsApplication(): void
    {
        $customCommand = new CodeStyleCommand(new Filesystem());

        $commandLoader = $this->prophesize(CommandLoaderInterface::class);
        $commandLoader->getNames()
            ->willReturn(['code-style']);
        $commandLoader->has('code-style')
            ->willReturn(true);
        $commandLoader->get('code-style')
            ->willReturn($customCommand);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(DevTools::class)
            ->willReturn(new DevTools($commandLoader->reveal()))
            ->shouldBeCalledOnce();

        $this->setStaticContainer($container->reveal());

        $commands = $this->commandProvider->getCommands();

        self::assertCount(1, $commands);
        self::assertSame($customCommand, $commands[0]);
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommandsWillReturnOnlyCommandInstances(): void
    {
        $customCommand = new CodeStyleCommand(new Filesystem());

        $commandLoader = $this->prophesize(CommandLoaderInterface::class);
        $commandLoader->getNames()
            ->willReturn(['code-style']);
        $commandLoader->has('code-style')
            ->willReturn(true);
        $commandLoader->get('code-style')
            ->willReturn($customCommand);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(DevTools::class)->willReturn(new DevTools($commandLoader->reveal()));

        $this->setStaticContainer($container->reveal());

        foreach ($this->commandProvider->getCommands() as $command) {
            self::assertInstanceOf(CodeStyleCommand::class, $command);
        }
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return void
     */
    private function setStaticContainer(?ContainerInterface $container): void
    {
        $property = new ReflectionProperty(StaticContainer::class, 'container');
        $property->setValue(null, $container);
    }
}
