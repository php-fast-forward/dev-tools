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

use FastForward\DevTools\Config\RectorConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RectorConfig::class)]
final class RectorConfigTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function configureWillReturnCallable(): void
    {
        $result = RectorConfig::configure();

        self::assertIsCallable($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWithCustomCallbackWillReturnCallable(): void
    {
        $customCallback = static function ($config): void {
            \assert($config instanceof \Rector\Config\RectorConfig);
        };

        $result = RectorConfig::configure($customCallback);

        self::assertIsCallable($result);
    }
}
