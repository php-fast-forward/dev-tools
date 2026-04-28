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

namespace FastForward\DevTools\Tests\ServiceProvider;

use DI\Container;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;

use function Safe\chdir;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\tempnam;
use function Safe\unlink;

#[CoversClass(DevToolsServiceProvider::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
final class DevToolsServiceProviderTest extends TestCase
{
    private DevToolsServiceProvider $provider;

    private string $originalWorkingDirectory;

    private string $workspaceDirectory;

    private string $workspaceResourcePath;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->provider = new DevToolsServiceProvider();
        $this->originalWorkingDirectory = getcwd();
        $this->workspaceDirectory = tempnam(sys_get_temp_dir(), 'dev-tools-service-provider-');
        unlink($this->workspaceDirectory);
        mkdir($this->workspaceDirectory);
        $this->workspaceResourcePath = $this->workspaceDirectory . '/local-resource.txt';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);

        if (file_exists($this->workspaceResourcePath)) {
            unlink($this->workspaceResourcePath);
        }

        if (is_dir($this->workspaceDirectory)) {
            rmdir($this->workspaceDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function implementsServiceProviderInterface(): void
    {
        self::assertInstanceOf(ServiceProviderInterface::class, $this->provider);
    }

    /**
     * @return void
     */
    #[Test]
    public function getExtensionsReturnEmptyArray(): void
    {
        self::assertEmpty($this->provider->getExtensions());
    }

    /**
     * @return void
     */
    #[Test]
    public function getFactoriesReturnFactories(): void
    {
        $factories = $this->provider->getFactories();

        self::assertIsArray($factories);
        self::assertNotEmpty($factories);
    }

    /**
     * @return void
     */
    #[Test]
    public function fileLocatorResolvesWorkingProjectPathWhenTheServiceIsRequested(): void
    {
        $container = new Container($this->provider->getFactories());
        file_put_contents($this->workspaceResourcePath, 'fixture');

        chdir($this->workspaceDirectory);

        $fileLocator = $container->get(FileLocatorInterface::class);

        self::assertInstanceOf(FileLocatorInterface::class, $fileLocator);
        self::assertSame($this->workspaceResourcePath, $fileLocator->locate('local-resource.txt'));
    }
}
