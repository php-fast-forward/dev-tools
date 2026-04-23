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

namespace FastForward\DevTools\Tests\Path;

use InvalidArgumentException;
use FastForward\DevTools\Path\DevToolsPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DevToolsPathResolver::class)]
final class DevToolsPathResolverTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function itWillExposeCanonicalPackagePaths(): void
    {
        self::assertSame(\dirname(__DIR__, 2), DevToolsPathResolver::getPackagePath());
        self::assertSame(\dirname(__DIR__, 2) . '/bin/dev-tools', DevToolsPathResolver::getBinaryPath());
        self::assertSame(\dirname(__DIR__, 2) . '/resources', DevToolsPathResolver::getResourcesPath());
        self::assertSame(
            \dirname(__DIR__, 2) . '/resources/phpdocumentor.xml',
            DevToolsPathResolver::getResourcesPath('phpdocumentor.xml')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillRejectAbsolutePackagePaths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The DevTools package path MUST be relative to the package root.');

        DevToolsPathResolver::getPackagePath('/tmp/dev-tools.php');
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillDetectWhetherDevToolsRunsFromVendorOrRepositoryCheckout(): void
    {
        self::assertTrue(
            DevToolsPathResolver::isInstalledAsDependency('/workspaces/project/vendor/fast-forward/dev-tools/src')
        );
        self::assertFalse(DevToolsPathResolver::isInstalledAsDependency('/workspaces/dev-tools/src'));
        self::assertTrue(DevToolsPathResolver::isRepositoryCheckout('/workspaces/dev-tools/src'));
        self::assertFalse(
            DevToolsPathResolver::isRepositoryCheckout('/workspaces/project/vendor/fast-forward/dev-tools/src')
        );
    }
}
