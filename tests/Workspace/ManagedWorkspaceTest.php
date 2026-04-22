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

namespace FastForward\DevTools\Tests\Workspace;

use FastForward\DevTools\Workspace\ManagedWorkspace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManagedWorkspace::class)]
final class ManagedWorkspaceTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function itWillExposeCanonicalRepositoryManagedPaths(): void
    {
        self::assertSame('.dev-tools', ManagedWorkspace::ROOT);
        self::assertSame('.dev-tools/cache', ManagedWorkspace::CACHE);
        self::assertSame('.dev-tools/coverage', ManagedWorkspace::COVERAGE);
        self::assertSame('.dev-tools/metrics', ManagedWorkspace::METRICS);
        self::assertSame('.dev-tools/release-notes.md', ManagedWorkspace::RELEASE_NOTES);
        self::assertSame('.dev-tools/cache/phpdoc', ManagedWorkspace::phpDocumentorCache());
        self::assertSame('.dev-tools/cache/phpunit', ManagedWorkspace::phpUnitCache());
        self::assertSame('.dev-tools/cache/rector', ManagedWorkspace::rectorCache());
        self::assertSame('.dev-tools/cache/php-cs-fixer', ManagedWorkspace::phpCsFixerCache());
    }
}
