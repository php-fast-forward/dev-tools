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

namespace FastForward\DevTools\Config;

use Composer\InstalledVersions;
use Ergebnis\Rector\Rules\Faker\GeneratorPropertyFetchToMethodCallRector;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use FastForward\DevTools\Rector\RemoveEmptyDocBlockRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\PhpLevelSetResolver;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\Php\PhpVersionResolver\ComposerJsonPhpVersionResolver;
use Rector\Set\ValueObject\SetList;

use function Safe\getcwd;

/**
 * Factory for creating and extending the Rector configuration.
 *
 * This factory allows consumers to build on top of the base configuration provided
 * by the dev-tools package instead of replacing it entirely. Consumers can extend
 * the configuration with custom rules, skips, paths, and other options while
 * preserving the ability to receive upstream updates automatically.
 *
 * Usage examples:
 *
 * 1. Using the factory method (recommended):
 *    ```php
 *    use FastForward\DevTools\Config\RectorConfigFactory;
 *
 *    return static function (RectorConfig $rectorConfig): void {
 *        RectorConfigFactory::configure($rectorConfig);
 *        // Add custom configuration
 *    };
 *    ```
 *
 * 2. Using the require pattern:
 *    ```php
 *    return require __DIR__ . '/vendor/fast-forward/dev-tools/rector.php';
 *    ```
 *
 * 3. Extending with custom rules:
 *    ```php
 *    $configure = require __DIR__ . '/vendor/fast-forward/dev-tools/rector.php';
 *    return static function (RectorConfig $rectorConfig) use ($configure): void {
 *        $configure($rectorConfig);
 *        $rectorConfig->rules([CustomRule::class]);
 *    };
 *    ```
 */
final class RectorConfigFactory
{
    /**
     * Configures the given RectorConfig with the base dev-tools rules.
     *
     * This method applies the base configuration that consumers can further
     * customize with additional rules, paths, skips, or configured rules.
     *
     * @param RectorConfig $rectorConfig the Rector configuration to configure
     *
     * @return void
     */
    public static function configure(RectorConfig $rectorConfig): void
    {
        $cwd = getcwd();

        $rectorConfig->sets([
            SetList::DEAD_CODE,
            SetList::CODE_QUALITY,
            SetList::CODING_STYLE,
            SetList::TYPE_DECLARATION,
            SetList::PRIVATIZATION,
            SetList::INSTANCEOF,
            SetList::EARLY_RETURN,
        ]);
        $rectorConfig->paths([$cwd]);
        $rectorConfig->skip([
            $cwd . '/public',
            $cwd . '/resources',
            $cwd . '/vendor',
            $cwd . '/tmp',
            RemoveUselessReturnTagRector::class,
            RemoveUselessParamTagRector::class,
        ]);
        $rectorConfig->cacheDirectory($cwd . '/tmp/cache/rector');
        $rectorConfig->importNames();
        $rectorConfig->removeUnusedImports();
        $rectorConfig->fileExtensions(['php']);
        $rectorConfig->parallel(600);
        $rectorConfig->rules([
            GeneratorPropertyFetchToMethodCallRector::class,
            AddMissingMethodPhpDocRector::class,
            RemoveEmptyDocBlockRector::class,
        ]);

        $projectPhpVersion = ComposerJsonPhpVersionResolver::resolveFromCwdOrFail();
        $phpLevelSets = PhpLevelSetResolver::resolveFromPhpVersion($projectPhpVersion);

        $rectorConfig->sets($phpLevelSets);

        if (InstalledVersions::isInstalled('thecodingmachine/safe', false)) {
            $packageLocation = InstalledVersions::getInstallPath('thecodingmachine/safe');
            $safeRectorMigrateFile = $packageLocation . '/rector-migrate.php';

            if (file_exists($safeRectorMigrateFile)) {
                $callback = require_once $safeRectorMigrateFile;

                if (is_callable($callback)) {
                    $callback($rectorConfig);
                }
            }
        }
    }

    /**
     * Creates a configuration closure that applies the base dev-tools Rector configuration.
     *
     * This method returns a closure that can be used to configure a RectorConfig instance.
     * The closure accepts an optional working directory to allow flexible usage across
     * different projects.
     *
     * @param string|null $workingDirectory the working directory to use
     *
     * @return \Closure the configuration closure
     */
    public static function configureWithWorkingDirectory(?string $workingDirectory = null): \Closure
    {
        $cwd = $workingDirectory ?? getcwd();

        return static function (RectorConfig $rectorConfig) use ($cwd): void {
            $rectorConfig->sets([
                SetList::DEAD_CODE,
                SetList::CODE_QUALITY,
                SetList::CODING_STYLE,
                SetList::TYPE_DECLARATION,
                SetList::PRIVATIZATION,
                SetList::INSTANCEOF,
                SetList::EARLY_RETURN,
            ]);
            $rectorConfig->paths([$cwd]);
            $rectorConfig->skip([
                $cwd . '/public',
                $cwd . '/resources',
                $cwd . '/vendor',
                $cwd . '/tmp',
                RemoveUselessReturnTagRector::class,
                RemoveUselessParamTagRector::class,
            ]);
            $rectorConfig->cacheDirectory($cwd . '/tmp/cache/rector');
            $rectorConfig->importNames();
            $rectorConfig->removeUnusedImports();
            $rectorConfig->fileExtensions(['php']);
            $rectorConfig->parallel(600);
            $rectorConfig->rules([
                GeneratorPropertyFetchToMethodCallRector::class,
                AddMissingMethodPhpDocRector::class,
                RemoveEmptyDocBlockRector::class,
            ]);

            $projectPhpVersion = ComposerJsonPhpVersionResolver::resolveFromCwdOrFail();
            $phpLevelSets = PhpLevelSetResolver::resolveFromPhpVersion($projectPhpVersion);

            $rectorConfig->sets($phpLevelSets);

            if (InstalledVersions::isInstalled('thecodingmachine/safe', false)) {
                $packageLocation = InstalledVersions::getInstallPath('thecodingmachine/safe');
                $safeRectorMigrateFile = $packageLocation . '/rector-migrate.php';

                if (file_exists($safeRectorMigrateFile)) {
                    $callback = require_once $safeRectorMigrateFile;

                    if (is_callable($callback)) {
                        $callback($rectorConfig);
                    }
                }
            }
        };
    }
}
