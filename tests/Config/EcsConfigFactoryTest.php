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

namespace FastForward\DevTools\Tests\Config;

use FastForward\DevTools\Config\EcsConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;

#[CoversClass(EcsConfigFactory::class)]
final class EcsConfigFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function createWillReturnEcsConfigBuilderInstance(): void
    {
        $config = EcsConfigFactory::create();

        self::assertInstanceOf(ECSConfigBuilder::class, $config);
    }

    /**
     * @return void
     */
    #[Test]
    public function loadBaseConfigurationWillReturnEcsConfigBuilderInstance(): void
    {
        $config = EcsConfigFactory::loadBaseConfiguration();

        self::assertInstanceOf(ECSConfigBuilder::class, $config);
    }

    /**
     * @return void
     */
    #[Test]
    public function loadBaseConfigurationWithCustomWorkingDirectory(): void
    {
        $config = EcsConfigFactory::loadBaseConfiguration('/custom/path');

        self::assertInstanceOf(ECSConfigBuilder::class, $config);
    }

    /**
     * @return void
     */
    #[Test]
    public function createWithCustomWorkingDirectoryWillReturnEcsConfigBuilderInstance(): void
    {
        $config = EcsConfigFactory::create();

        self::assertInstanceOf(ECSConfigBuilder::class, $config);
    }
}
