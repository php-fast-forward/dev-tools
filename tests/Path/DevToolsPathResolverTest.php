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
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

#[CoversClass(DevToolsPathResolver::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
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
        self::assertSame(\dirname(__DIR__, 2) . '/vendor/autoload.php', DevToolsPathResolver::getRuntimeAutoloadPath());
        self::assertSame(
            \dirname(__DIR__, 2) . '/vendor/bin/ecs',
            DevToolsPathResolver::getRuntimeToolBinaryPath('ecs')
        );
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

        self::assertTrue(
            DevToolsPathResolver::isInstalledAsDependency('C:/workspaces/project/vendor/fast-forward/dev-tools/src')
        );
        self::assertTrue(DevToolsPathResolver::isRepositoryCheckout('/workspaces/dev-tools/src'));
        self::assertFalse(
            DevToolsPathResolver::isRepositoryCheckout('/workspaces/project/vendor/fast-forward/dev-tools/src')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillResolveRuntimeAutoloadPathsForRepositoryAndDependencyInstalls(): void
    {
        self::assertSame(
            '/workspaces/dev-tools/vendor/autoload.php',
            DevToolsPathResolver::getRuntimeAutoloadPath('/workspaces/dev-tools')
        );
        self::assertSame(
            '/workspaces/project/vendor/autoload.php',
            DevToolsPathResolver::getRuntimeAutoloadPath('/workspaces/project/vendor/fast-forward/dev-tools')
        );
    }

    /**
     * @param string $binary
     *
     * @return void
     */
    #[Test]
    #[TestWith(['php-cs-fixer'])]
    #[TestWith(['rector'])]
    #[TestWith(['ecs'])]
    #[TestWith(['jack'])]
    #[TestWith(['composer-dependency-analyser'])]
    public function itWillResolveRuntimeToolBinaryPathsForRepositoryAndDependencyInstalls(string $binary): void
    {
        self::assertSame(
            '/workspaces/dev-tools/vendor/bin/' . $binary,
            DevToolsPathResolver::getRuntimeToolBinaryPath($binary, '/workspaces/dev-tools')
        );
        self::assertSame(
            '/workspaces/project/vendor/bin/' . $binary,
            DevToolsPathResolver::getRuntimeToolBinaryPath($binary, '/workspaces/project/vendor/fast-forward/dev-tools')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillPreferProjectToolBinariesWhenTheyExist(): void
    {
        $projectPath = sys_get_temp_dir() . '/dev-tools-path-resolver-' . bin2hex(random_bytes(4));
        $binaryPath = $projectPath . '/vendor/bin/ecs';

        mkdir($projectPath . '/vendor/bin', 0o777, true);
        file_put_contents($binaryPath, '#!/usr/bin/env php');

        try {
            self::assertSame(
                $binaryPath,
                DevToolsPathResolver::getPreferredToolBinaryPath(
                    'ecs',
                    $projectPath,
                    '/Users/example/.composer/vendor/fast-forward/dev-tools'
                )
            );
        } finally {
            unlink($binaryPath);
            rmdir($projectPath . '/vendor/bin');
            rmdir($projectPath . '/vendor');
            rmdir($projectPath);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillFallbackToRuntimeToolBinariesWhenTheProjectDoesNotProvideThem(): void
    {
        self::assertSame(
            '/Users/example/.composer/vendor/bin/jack',
            DevToolsPathResolver::getPreferredToolBinaryPath(
                'jack',
                '/workspaces/project',
                '/Users/example/.composer/vendor/fast-forward/dev-tools'
            )
        );
    }
}
