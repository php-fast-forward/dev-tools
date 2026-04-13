<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\DevTools\Tests\ServiceProvider;

use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Interop\Container\ServiceProviderInterface;

#[CoversClass(DevToolsServiceProvider::class)]
final class DevToolsServiceProviderTest extends TestCase
{
    private DevToolsServiceProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new DevToolsServiceProvider();
    }

    #[Test]
    public function implementsServiceProviderInterface(): void
    {
        self::assertInstanceOf(ServiceProviderInterface::class, $this->provider);
    }

    #[Test]
    public function getExtensionsReturnEmptyArray(): void
    {
        self::assertEmpty($this->provider->getExtensions());
    }

    #[Test]
    public function getFactoriesReturnFactories(): void
    {
        $factories = $this->provider->getFactories();

        self::assertIsArray($factories);
        self::assertNotEmpty($factories);
    }
}
