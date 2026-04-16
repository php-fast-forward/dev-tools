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

namespace FastForward\DevTools\Tests\Config;

use FastForward\DevTools\Config\ECSConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;

#[CoversClass(ECSConfig::class)]
final class ECSConfigTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function configureWillReturnECSConfigBuilder(): void
    {
        $result = ECSConfig::configure();

        self::assertInstanceOf(ECSConfigBuilder::class, $result);
    }
}
