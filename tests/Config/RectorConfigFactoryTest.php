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

use FastForward\DevTools\Config\RectorConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Rector\Config\RectorConfig;

#[CoversClass(RectorConfigFactory::class)]
final class RectorConfigFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function configureWillConfigureRectorConfig(): void
    {
        $rectorConfig = new RectorConfig();

        RectorConfigFactory::configure($rectorConfig);

        self::assertNotNull($rectorConfig);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWithWorkingDirectoryWillReturnClosure(): void
    {
        $closure = RectorConfigFactory::configureWithWorkingDirectory();

        self::assertIsCallable($closure);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWithCustomWorkingDirectoryWillReturnClosure(): void
    {
        $closure = RectorConfigFactory::configureWithWorkingDirectory('/custom/path');

        self::assertIsCallable($closure);
    }
}
