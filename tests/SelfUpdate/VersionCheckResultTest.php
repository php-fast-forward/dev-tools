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

namespace FastForward\DevTools\Tests\SelfUpdate;

use FastForward\DevTools\SelfUpdate\VersionCheckResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(VersionCheckResult::class)]
final class VersionCheckResultTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function isOutdatedWillReturnTrueWhenLatestStableVersionIsNewer(): void
    {
        $result = new VersionCheckResult('1.2.0', 'v1.3.0');

        self::assertTrue($result->isOutdated());
        self::assertSame('1.2.0', $result->getCurrentVersion());
        self::assertSame('v1.3.0', $result->getLatestVersion());
    }

    /**
     * @return void
     */
    #[Test]
    public function isOutdatedWillReturnFalseWhenVersionsMatch(): void
    {
        $result = new VersionCheckResult('1.3.0', 'v1.3.0');

        self::assertFalse($result->isOutdated());
    }
}
