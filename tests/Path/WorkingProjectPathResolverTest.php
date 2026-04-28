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

use function Safe\scandir;
use function Safe\rmdir;
use function Safe\unlink;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;
use function Safe\putenv;
use function Safe\realpath;
use function uniqid;

#[CoversClass(WorkingProjectPathResolver::class)]
#[UsesClass(ManagedWorkspace::class)]
final class WorkingProjectPathResolverTest extends TestCase
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
    public function itWillExposeCanonicalRepositoryRootPaths(): void
    {
        self::assertSame(
            [
                'repo/.dev-tools',
                'repo/backup',
                'repo/cache',
                'repo/public',
                'repo/resources',
                'repo/tmp',
                'repo/vendor',
                'repo/*/vendor',
                'repo/*/vendor/*',
                'repo/**/vendor',
                'repo/**/vendor/*',
            ],
            WorkingProjectPathResolver::getToolingExcludedDirectories('repo')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillIncludeCustomRelativeWorkspaceInToolingSkipPatterns(): void
    {
        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=.artifacts');

        self::assertSame(
            [
                'repo/.dev-tools',
                'repo/backup',
                'repo/cache',
                'repo/public',
                'repo/resources',
                'repo/tmp',
                'repo/vendor',
                'repo/*/vendor',
                'repo/*/vendor/*',
                'repo/**/vendor',
                'repo/**/vendor/*',
                'repo/.artifacts',
            ],
            WorkingProjectPathResolver::getToolingExcludedDirectories('repo')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillNormalizePathSeparatorsWhenJoiningProjectPaths(): void
    {
        self::assertSame(
            [
                'tmp/.dev-tools',
                'tmp/backup',
                'tmp/cache',
                'tmp/public',
                'tmp/resources',
                'tmp/tmp',
                'tmp/vendor',
                'tmp/*/vendor',
                'tmp/*/vendor/*',
                'tmp/**/vendor',
                'tmp/**/vendor/*',
            ],
            WorkingProjectPathResolver::getToolingExcludedDirectories('tmp/')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeRelativeToolingSkipPatternsByDefault(): void
    {
        self::assertSame(
            [
                '.dev-tools',
                'backup',
                'cache',
                'public',
                'resources',
                'tmp',
                'vendor',
                '*/vendor',
                '*/vendor/*',
                '**/vendor',
                '**/vendor/*',
            ],
            WorkingProjectPathResolver::getToolingExcludedDirectories()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeToolingSourcePathsIgnoringExcludedDirectories(): void
    {
        $fixtureDirectory = \dirname(__DIR__, 2) . '/backup/dev-tools-path-resolver-' . uniqid();

        mkdir($fixtureDirectory . '/src', recursive: true);
        mkdir($fixtureDirectory . '/tests/Fixtures/consumer/vendor/package/src', recursive: true);
        mkdir($fixtureDirectory . '/resources', recursive: true);
        mkdir($fixtureDirectory . '/backup', recursive: true);
        mkdir($fixtureDirectory . '/.dev-tools/cache', recursive: true);

        file_put_contents($fixtureDirectory . '/src/Example.php', '<?php');
        file_put_contents($fixtureDirectory . '/tests/Fixtures/Example.php', '<?php');
        file_put_contents($fixtureDirectory . '/tests/Fixtures/consumer/vendor/package/src/VendorExample.php', '<?php');
        file_put_contents($fixtureDirectory . '/resources/Resource.php', '<?php');
        file_put_contents($fixtureDirectory . '/backup/Backup.php', '<?php');
        file_put_contents($fixtureDirectory . '/.dev-tools/cache/Cached.php', '<?php');

        try {
            self::assertSame(
                [
                    realpath($fixtureDirectory) . '/src/Example.php',
                    realpath($fixtureDirectory) . '/tests/Fixtures/Example.php',
                ],
                WorkingProjectPathResolver::getToolingSourcePaths(realpath($fixtureDirectory))
            );
        } finally {
            self::cleanupFixtureDirectory($fixtureDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillIgnoreCustomWorkspaceWhenResolvingToolingSourcePaths(): void
    {
        $fixtureDirectory = \dirname(__DIR__, 2) . '/backup/dev-tools-path-resolver-' . uniqid();

        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=.artifacts');

        mkdir($fixtureDirectory . '/src', recursive: true);
        mkdir($fixtureDirectory . '/.artifacts/cache', recursive: true);

        file_put_contents($fixtureDirectory . '/src/Example.php', '<?php');
        file_put_contents($fixtureDirectory . '/.artifacts/cache/Cached.php', '<?php');

        try {
            self::assertSame(
                [realpath($fixtureDirectory) . '/src/Example.php'],
                WorkingProjectPathResolver::getToolingSourcePaths(realpath($fixtureDirectory))
            );
        } finally {
            self::cleanupFixtureDirectory($fixtureDirectory);
        }
    }

    /**
     * @param string $fixtureDirectory
     *
     * @return void
     */
    private static function cleanupFixtureDirectory(string $fixtureDirectory): void
    {
        if (! is_dir($fixtureDirectory) && ! is_link($fixtureDirectory)) {
            return;
        }

        $entries = scandir($fixtureDirectory);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry) {
                continue;
            }

            if ('..' === $entry) {
                continue;
            }

            $path = $fixtureDirectory . '/' . $entry;

            if (is_link($path) || is_file($path)) {
                unlink($path);
                continue;
            }

            self::cleanupFixtureDirectory($path);
        }

        rmdir($fixtureDirectory);
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeTheCurrentWorkingProjectDirectoryOrPathsUnderIt(): void
    {
        self::assertSame(getcwd(), WorkingProjectPathResolver::getProjectPath());
        self::assertSame(getcwd() . '/resources', WorkingProjectPathResolver::getProjectPath('resources'));
        self::assertSame(
            '/tmp/project/resources',
            WorkingProjectPathResolver::getProjectPath('/tmp/project/resources')
        );
    }
}
