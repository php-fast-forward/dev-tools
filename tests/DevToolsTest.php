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

namespace FastForward\DevTools\Tests;

use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\DevTools;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;

#[CoversClass(DevTools::class)]
final class DevToolsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CommandProvider>
     */
    private ObjectProphecy $commandProvider;

    private DevTools $devTools;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        $this->commandProvider = $this->prophesize(CommandProvider::class);
        $this->devTools = new DevTools($this->commandProvider->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getDefaultCommandsWillMergeProvidedCommandsWithFrameworkDefaults(): void
    {
        $firstCommand = new class extends Command {};

        $secondCommand = new class extends Command {};

        $this->commandProvider
            ->getCommands()
            ->willReturn([$firstCommand, $secondCommand])
            ->shouldBeCalledOnce();

        $commands = $this->invokeGetDefaultCommands($this->devTools);

        self::assertCount(6, $commands);
        self::assertSame($firstCommand, $commands[0]);
        self::assertSame($secondCommand, $commands[1]);
        self::assertInstanceOf(HelpCommand::class, $commands[2]);
        self::assertInstanceOf(ListCommand::class, $commands[3]);
        self::assertInstanceOf(CompleteCommand::class, $commands[4]);
        self::assertInstanceOf(DumpCompletionCommand::class, $commands[5]);
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillSetApplicationName(): void
    {
        self::assertSame('Fast Forward Dev Tools', $this->devTools->getName());
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
