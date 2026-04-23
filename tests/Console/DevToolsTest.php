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

namespace FastForward\DevTools\Tests\Console;

use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Filesystem\FinderFactory;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

#[CoversClass(DevTools::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(DevToolsCommandLoader::class)]
#[UsesClass(FinderFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
final class DevToolsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CommandLoaderInterface>
     */
    private ObjectProphecy $commandLoader;

    private DevTools $devTools;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        $this->commandLoader = $this->prophesize(CommandLoaderInterface::class);
        $this->commandLoader->getNames()
            ->willReturn([]);
        $this->devTools = new DevTools($this->commandLoader->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getDefaultCommandsWillReturnFrameworkDefaultsOnly(): void
    {
        $commands = $this->invokeGetDefaultCommands($this->devTools);

        self::assertCount(4, $commands);
        self::assertInstanceOf(HelpCommand::class, $commands[0]);
        self::assertInstanceOf(ListCommand::class, $commands[1]);
        self::assertInstanceOf(CompleteCommand::class, $commands[2]);
        self::assertInstanceOf(DumpCompletionCommand::class, $commands[3]);
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillSetApplicationNameAndExposeLoaderCommands(): void
    {
        $customCommand = new class extends Command {
            public function __construct()
            {
                parent::__construct('custom');
            }
        };

        $this->commandLoader->getNames()
            ->willReturn(['custom']);
        $this->commandLoader->has('custom')
            ->willReturn(true);
        $this->commandLoader->get('custom')
            ->willReturn($customCommand);

        self::assertSame('Fast Forward Dev Tools', $this->devTools->getName());
        self::assertTrue($this->devTools->has('custom'));
        self::assertSame($customCommand, $this->devTools->get('custom'));
    }

    /**
     * @return void
     */
    #[Test]
    public function createWillReturnInstanceOfDevTools(): void
    {
        $reflectionProperty = new ReflectionProperty(DevTools::class, 'container');
        $reflectionProperty->setValue(null, null);

        $devTools = DevTools::create();

        self::assertInstanceOf(DevTools::class, $devTools);
        self::assertSame($devTools, DevTools::create());
    }

    /**
     * @param DevTools $devTools
     *
     * @return array<int, Command>
     */
    private function invokeGetDefaultCommands(DevTools $devTools): array
    {
        $reflectionMethod = new ReflectionMethod($devTools, 'getDefaultCommands');

        /** @var array<int, Command> $commands */
        $commands = $reflectionMethod->invoke($devTools);

        return $commands;
    }
}
