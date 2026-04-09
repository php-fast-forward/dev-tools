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
 * Base Rector configuration for dev-tools.
 *
 * This configuration can be extended in consumer projects using one of the following patterns:
 *
 * 1. Use directly (recommended):
 *    ```php
 *    return require __DIR__ . '/vendor/fast-forward/dev-tools/rector.php';
 *    ```
 *
 * 2. Extend with custom rules:
 *    ```php
 *    $configure = require __DIR__ . '/vendor/fast-forward/dev-tools/rector.php';
 *    return static function (RectorConfig $rectorConfig) use ($configure): void {
 *        $configure($rectorConfig);
 *        $rectorConfig->rules([CustomRule::class]);
 *    };
 *    ```
 *
 * 3. Using the factory:
 *    ```php
 *    use FastForward\DevTools\Config\RectorConfigFactory;
 *
 *    return static function (RectorConfig $rectorConfig): void {
 *        RectorConfigFactory::configure($rectorConfig);
 *        // Add custom configuration
 *    };
 *    ```
 *
 * @return callable(RectorConfig): void the configuration closure that configures Rector
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
        SetList::EARLY_RETURN,
    ]);
    $rectorConfig->paths([getcwd()]);
    $rectorConfig->skip([
        getcwd() . '/public',
        getcwd() . '/resources',
        getcwd() . '/vendor',
        getcwd() . '/tmp',
        RemoveUselessReturnTagRector::class,
        RemoveUselessParamTagRector::class,
    ]);
    $rectorConfig->cacheDirectory(getcwd() . '/tmp/cache/rector');
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
