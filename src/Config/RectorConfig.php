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

namespace FastForward\DevTools\Config;

use Composer\InstalledVersions;
use Ergebnis\Rector\Rules\Faker\GeneratorPropertyFetchToMethodCallRector;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use FastForward\DevTools\Rector\RemoveEmptyDocBlockRector;
use Rector\Config\RectorConfig as RectorConfigInterface;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\Php\PhpVersionResolver\ComposerJsonPhpVersionResolver;
use Rector\Configuration\PhpLevelSetResolver;
use Rector\Set\ValueObject\SetList;

use function Safe\getcwd;

/**
 * Provides the default Rector configuration.
 *
 * Consumers can use this as a starting point and extend it:
 *
 *     return \FastForward\DevTools\Config\RectorConfig::configure(
 *         static function (\Rector\Config\RectorConfig $rectorConfig): void {
 *             $rectorConfig->rules([
 *                 // custom rules
 *             ]);
 *         }
 *     );
 *
 * @see https://github.com/rectorphp/rector
 */
final class RectorConfig
{
    /**
     * Creates the default Rector configuration.
     *
     * @param callable|null $customize optional callback to customize the configuration
     *
     * @return callable the configuration callback
     */
    public static function configure(?callable $customize = null): callable
    {
        return static function (RectorConfigInterface $rectorConfig) use ($customize): void {
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

                    if (\is_callable($callback)) {
                        $callback($rectorConfig);
                    }
                }
            }

            if (null !== $customize) {
                $customize($rectorConfig);
            }
        };
    }
}
