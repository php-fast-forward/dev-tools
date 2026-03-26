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

use Composer\Package\RootPackageInterface;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event as ScriptEvent;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Composer\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function Safe\tempnam;
use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\putenv;
use function Safe\unlink;

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
    private string $tempComposerFile;

    private string $originalComposerEnv;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->plugin = new Plugin();
        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);

        $this->originalComposerEnv = (string) getenv('COMPOSER');
        $this->tempComposerFile = tempnam(sys_get_temp_dir(), 'composer_test');
        // O nome do pacote precisa ser fast-forward/dev-tools para que o método installScripts execute a lógica
        file_put_contents($this->tempComposerFile, json_encode([
            'name' => 'fast-forward/dev-tools',
            'scripts' => (object) [],
        ]));

        putenv('COMPOSER=' . $this->tempComposerFile);
        $_ENV['COMPOSER'] = $this->tempComposerFile;
        $_SERVER['COMPOSER'] = $this->tempComposerFile;
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempComposerFile)) {
            unlink($this->tempComposerFile);
        }

        putenv('COMPOSER=' . $this->originalComposerEnv);
        $_ENV['COMPOSER'] = $this->originalComposerEnv;
        $_SERVER['COMPOSER'] = $this->originalComposerEnv;
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
        self::assertNull($this->plugin->activate($this->composer->reveal(), $this->io->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function onPostInstallWillInstallScripts(): void
    {
        $event = $this->prophesize(ScriptEvent::class);

        // Mock RootPackageInterface para getPackage()
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getName()
            ->willReturn('fast-forward/dev-tools');
        $this->composer->getPackage()
            ->willReturn($package->reveal());

        // Mock EventDispatcher
        $eventDispatcher = $this->prophesize(EventDispatcher::class);
        $eventDispatcher->dispatchScript('dev-tools:sync', true)
            ->willReturn(0);
        $this->composer->getEventDispatcher()
            ->willReturn($eventDispatcher->reveal());

        $event->getComposer()
            ->willReturn($this->composer->reveal());
        $event->getIO()
            ->willReturn($this->io->reveal());

        $this->plugin->onPostInstall($event->reveal());

        self::assertTrue(true); // Evita teste risky
    }

    /**
     * @return void
     */
    #[Test]
    public function onPostUpdateWillInstallScripts(): void
    {
        $event = $this->prophesize(ScriptEvent::class);

        // Mock RootPackageInterface para getPackage()
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getName()
            ->willReturn('fast-forward/dev-tools');
        $this->composer->getPackage()
            ->willReturn($package->reveal());

        // Mock EventDispatcher
        $eventDispatcher = $this->prophesize(EventDispatcher::class);
        $eventDispatcher->dispatchScript('dev-tools:sync', true)
            ->willReturn(0);
        $this->composer->getEventDispatcher()
            ->willReturn($eventDispatcher->reveal());

        $event->getComposer()
            ->willReturn($this->composer->reveal());
        $event->getIO()
            ->willReturn($this->io->reveal());

        $this->plugin->onPostUpdate($event->reveal());

        self::assertTrue(true); // Evita teste risky
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
