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

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\BootstrapResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BootstrapResult::class)]
final class BootstrapResultTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function createWillReturnInstanceWithAllPropertiesSet(): void
    {
        $result = new BootstrapResult(true, true, false);

        self::assertTrue($result->configCreated);
        self::assertTrue($result->changelogCreated);
        self::assertFalse($result->unreleasedCreated);
    }

    /**
     * @return void
     */
    #[Test]
    public function createWillAllowMixedBooleanValues(): void
    {
        $result = new BootstrapResult(false, true, true);

        self::assertFalse($result->configCreated);
        self::assertTrue($result->changelogCreated);
        self::assertTrue($result->unreleasedCreated);
    }
}
