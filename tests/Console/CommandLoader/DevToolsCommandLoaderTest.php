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

namespace FastForward\DevTools\Tests\Console\CommandLoader;

use ArrayIterator;
use FastForward\DevTools\Console\Command\CodeStyleCommand;
use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
     * @var ObjectProphecy<FinderFactoryInterface>
     */
    private ObjectProphecy $finderFactory;

    /**
     * @var ObjectProphecy<Finder>
     */
    private ObjectProphecy $finder;

    /**
     * @var ObjectProphecy<ContainerInterface>
     */
    private ObjectProphecy $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->finderFactory = $this->prophesize(FinderFactoryInterface::class);
        $this->finder = $this->prophesize(Finder::class);
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillRegisterOnlyInstantiableCommands(): void
    {
        $commandDirectory = \dirname(__DIR__, 3) . '/src/Console/Command';
        $command = $this->prophesize(Command::class);

        $this->finderFactory->create()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalledOnce();
        $this->finder->files()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->in(Argument::type('string'))->willReturn($this->finder->reveal())->shouldBeCalled();
        $this->finder->notPath('Traits')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->name('*.php')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->getIterator()
            ->willReturn(new ArrayIterator([
                new SplFileInfo($commandDirectory . '/CodeStyleCommand.php', '', 'CodeStyleCommand.php'),
            ]))->shouldBeCalled();

        $this->container->has(CodeStyleCommand::class)->willReturn(true)->shouldBeCalled();
        $this->container->get(CodeStyleCommand::class)->willReturn($command->reveal())->shouldBeCalled();

        $loader = new DevToolsCommandLoader($this->finderFactory->reveal(), $this->container->reveal());

        self::assertTrue($loader->has('code-style'));
        self::assertSame($command->reveal(), $loader->get('code-style'));
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillSkipClassesWithoutAsCommandAttribute(): void
    {
        $commandDirectory = \dirname(__DIR__, 3) . '/src/Console/Command';

        $this->finderFactory->create()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalledOnce();
        $this->finder->files()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->in(Argument::type('string'))->willReturn($this->finder->reveal())->shouldBeCalled();
        $this->finder->notPath('Traits')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->name('*.php')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->getIterator()
            ->willReturn(new ArrayIterator([
                new SplFileInfo($commandDirectory . '/FixtureWithoutAsCommand.php', '', 'FixtureWithoutAsCommand.php'),
            ]))->shouldBeCalled();

        $loader = new DevToolsCommandLoader($this->finderFactory->reveal(), $this->container->reveal());

        self::assertFalse($loader->has('abstract'));
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillSkipNonInstantiableAndNonCommandClasses(): void
    {
        $commandDirectory = \dirname(__DIR__, 3) . '/src/Console/Command';

        $this->finderFactory->create()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalledOnce();
        $this->finder->files()
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->in(Argument::type('string'))->willReturn($this->finder->reveal())->shouldBeCalled();
        $this->finder->notPath('Traits')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->name('*.php')
            ->willReturn($this->finder->reveal())
            ->shouldBeCalled();
        $this->finder->getIterator()
            ->willReturn(new ArrayIterator([
                new SplFileInfo($commandDirectory . '/FixtureAbstractCommand.php', '', 'FixtureAbstractCommand.php'),
                new SplFileInfo(
                    $commandDirectory . '/FixtureWithoutCommandParent.php',
                    '',
                    'FixtureWithoutCommandParent.php'
                ),
            ]))->shouldBeCalled();

        $loader = new DevToolsCommandLoader($this->finderFactory->reveal(), $this->container->reveal());

        self::assertFalse($loader->has('fixture-abstract'));
        self::assertFalse($loader->has('fixture-without-command-parent'));
    }
}
