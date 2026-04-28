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

namespace FastForward\DevTools\Tests\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event as ScriptEvent;
use Composer\Util\Loop;
use Composer\Util\ProcessExecutor;
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
     * @var ObjectProphecy<RootPackageInterface>
     */
    private ObjectProphecy $rootPackage;

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
        $this->rootPackage = $this->prophesize(RootPackageInterface::class);
        $this->composer->getPackage()
            ->willReturn($this->rootPackage->reveal());
        $this->rootPackage->getScripts()
            ->willReturn([]);

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
    public function getSubscribedEventsWillReturnExpectedEventMapping(): void
    {
        self::assertSame(
            [
                'post-install-cmd' => 'runSyncCommand',
                'post-update-cmd' => 'runSyncCommand',
            ],
            Plugin::getSubscribedEvents(),
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
    public function isRegisteredCommandWillDetectReservedCommandNames(): void
    {
        $this->rootPackage->getScripts()
            ->willReturn([
                'custom-script' => [],
                'post-install-cmd' => [],
            ]);
        $this->plugin->activate($this->composer->reveal(), $this->io->reveal());

        self::assertTrue($this->plugin->isRegisteredCommand('install'));
        self::assertTrue($this->plugin->isRegisteredCommand('i'));
        self::assertTrue($this->plugin->isRegisteredCommand('self-update'));
        self::assertTrue($this->plugin->isRegisteredCommand('selfupdate'));
        self::assertTrue($this->plugin->isRegisteredCommand('custom-script'));
        self::assertFalse($this->plugin->isRegisteredCommand('post-install-cmd'));
        self::assertFalse($this->plugin->isRegisteredCommand('code-style'));
        self::assertFalse($this->plugin->isRegisteredCommand(null));
    }

    /**
     * @return void
     */
    #[Test]
    public function runSyncCommandWillExecuteDevToolsSync(): void
    {
        $event = $this->prophesize(ScriptEvent::class);

        // Mock ProcessExecutor
        $processExecutor = $this->prophesize(ProcessExecutor::class);
        $processExecutor->execute('vendor/bin/dev-tools dev-tools:sync')
            ->shouldBeCalled();

        // Mock Loop
        $loop = $this->prophesize(Loop::class);
        $loop->getProcessExecutor()
            ->willReturn($processExecutor->reveal());

        // Mock Composer
        $composer = $this->prophesize(Composer::class);
        $composer->getLoop()
            ->willReturn($loop->reveal());

        $event->getComposer()
            ->willReturn($composer->reveal());

        $this->plugin->runSyncCommand($event->reveal());

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
