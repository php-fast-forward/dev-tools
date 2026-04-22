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

use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkingProjectPathResolver::class)]
#[UsesClass(ManagedWorkspace::class)]
final class WorkingProjectPathResolverTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function itWillExposeCanonicalRepositoryRootPaths(): void
    {
        self::assertSame('resources', WorkingProjectPathResolver::getResourcesDirectory());
        self::assertSame('vendor', WorkingProjectPathResolver::getVendorDirectory());
        self::assertSame('tmp/resources/phpdoc', WorkingProjectPathResolver::getResourcesDirectory('phpdoc', 'tmp'));
        self::assertSame('repo/vendor/bin', WorkingProjectPathResolver::getVendorDirectory('bin', 'repo'));
        self::assertSame(
            ['repo/.dev-tools', 'repo/resources', 'repo/vendor'],
            WorkingProjectPathResolver::getToolingExcludedDirectories('repo')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillNormalizePathSeparatorsWhenJoiningRepositoryRootPaths(): void
    {
        self::assertSame('tmp/resources/phpdoc', WorkingProjectPathResolver::getResourcesDirectory('/phpdoc', 'tmp/'));
        self::assertSame('tmp/vendor/bin', WorkingProjectPathResolver::getVendorDirectory('/bin', 'tmp/'));
    }
}
