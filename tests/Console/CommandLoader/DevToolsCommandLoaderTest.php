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

namespace FastForward\DevTools\Tests\Console\CommandLoader;

use ArrayIterator;
use FastForward\DevTools\Console\Command\CodeStyleCommand;
use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[CoversClass(DevToolsCommandLoader::class)]
#[UsesClass(CodeStyleCommand::class)]
final class DevToolsCommandLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function constructorWillRegisterOnlyInstantiableCommands(): void
    {
        $commandDirectory = \dirname(__DIR__, 3) . '/src/Console/Command';
        $command = $this->prophesize(Command::class);

        $finder = $this->prophesize(Finder::class);
        $finder->files()
            ->willReturn($finder->reveal())
            ->shouldBeCalled();
        $finder->in(Argument::type('string'))->willReturn($finder->reveal())->shouldBeCalled();
        $finder->name('*.php')
            ->willReturn($finder->reveal())
            ->shouldBeCalled();
        $finder->getIterator()
            ->willReturn(new ArrayIterator([
                new SplFileInfo($commandDirectory . '/CodeStyleCommand.php', '', 'CodeStyleCommand.php'),
            ]))->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(CodeStyleCommand::class)->willReturn(true)->shouldBeCalled();
        $container->get(CodeStyleCommand::class)->willReturn($command->reveal())->shouldBeCalled();

        $loader = new DevToolsCommandLoader($finder->reveal(), $container->reveal());

        self::assertTrue($loader->has('code-style'));
        self::assertSame($command->reveal(), $loader->get('code-style'));
    }
}
