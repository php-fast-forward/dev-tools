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

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->provider = new DevToolsServiceProvider();
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
}
