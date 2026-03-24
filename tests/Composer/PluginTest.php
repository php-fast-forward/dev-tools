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

namespace FastForward\DevTools\Tests\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Composer\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(Plugin::class)]
final class PluginTest extends TestCase
{
    use ProphecyTrait;

    private Plugin $plugin;

    /**
     * @var ObjectProphecy<Composer>
     */
    private ObjectProphecy $composer;

    /**
     * @var ObjectProphecy<IOInterface>
     */
    private ObjectProphecy $io;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->plugin = new Plugin();
        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function getCapabilitiesWillReturnDevToolsCommandProviderMapping(): void
    {
        self::assertSame(
            [
                CommandProvider::class => DevToolsCommandProvider::class,
            ],
            $this->plugin->getCapabilities(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function activateWillDoNothing(): void
    {
        self::markTestIncomplete('The activate method needs to be tested.');
    }

    /**
     * @return void
     */
    #[Test]
    public function deactivateWillDoNothing(): void
    {
        self::assertNull($this->plugin->deactivate($this->composer->reveal(), $this->io->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function uninstallWillDoNothing(): void
    {
        self::assertNull($this->plugin->uninstall($this->composer->reveal(), $this->io->reveal()));
    }
}
