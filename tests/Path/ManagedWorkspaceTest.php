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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\putenv;

#[CoversClass(ManagedWorkspace::class)]
final class ManagedWorkspaceTest extends TestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR);
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeCanonicalRepositoryManagedPaths(): void
    {
        self::assertSame('.dev-tools', ManagedWorkspace::getOutputDirectory());
        self::assertSame('.dev-tools/coverage', ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE));
        self::assertSame('.dev-tools/metrics', ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS));
        self::assertSame(
            'tmp/.dev-tools/metrics',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS, 'tmp')
        );
        self::assertSame('.dev-tools/cache', ManagedWorkspace::getCacheDirectory());
        self::assertSame('.dev-tools/cache/phpdoc', ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPDOC));
        self::assertSame('.dev-tools/cache/phpunit', ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT));
        self::assertSame('.dev-tools/cache/rector', ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR));
        self::assertSame(
            '.dev-tools/cache/php-cs-fixer',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHP_CS_FIXER)
        );
        self::assertSame(
            'tmp/.dev-tools/cache/rector',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR, 'tmp')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillNormalizePathSeparatorsWhenJoiningManagedPaths(): void
    {
        self::assertSame('tmp/.dev-tools/metrics', ManagedWorkspace::getOutputDirectory('/metrics', 'tmp/'));
        self::assertSame('tmp/.dev-tools/cache/phpunit', ManagedWorkspace::getCacheDirectory('/phpunit', 'tmp/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillUseConfiguredRelativeWorkspaceRoot(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=.artifacts');

        self::assertSame('.artifacts', ManagedWorkspace::getWorkspaceRoot());
        self::assertSame('.artifacts/coverage', ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE));
        self::assertSame(
            'tmp/.artifacts/cache/phpunit',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT, 'tmp')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillUseConfiguredAbsoluteWorkspaceRoot(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=/tmp/dev-tools-artifacts');

        self::assertSame('/tmp/dev-tools-artifacts', ManagedWorkspace::getWorkspaceRoot());
        self::assertSame(
            '/tmp/dev-tools-artifacts/metrics',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS, 'tmp')
        );
        self::assertSame(
            '/tmp/dev-tools-artifacts/cache/rector',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR, 'tmp')
        );
    }
}
