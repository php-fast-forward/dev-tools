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

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/**
 * Provides the default Composer Dependency Analyser configuration.
 *
 * Consumers can use this as a starting point and extend it:
 *
 *     return \FastForward\DevTools\Config\ComposerDependencyAnalyserConfig::configure(
 *         static function (\ShipMonk\ComposerDependencyAnalyser\Config\Configuration $configuration): void {
 *             $configuration->ignoreErrorsOnPackage(
 *                 'vendor/package',
 *                 [\ShipMonk\ComposerDependencyAnalyser\Config\ErrorType::UNUSED_DEPENDENCY]
 *             );
 *         }
 *     );
 *
 * @see https://github.com/shipmonk-rnd/composer-dependency-analyser
 */
final class ComposerDependencyAnalyserConfig
{
    private const string VENDOR_PACKAGE_PATH = \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR
        . 'fast-forward' . \DIRECTORY_SEPARATOR . 'dev-tools';

    /**
     * Dependencies that are only required by the packaged DevTools distribution itself.
     *
     * These packages MUST only be ignored when the analyser runs against the
     * DevTools repository, because consumer repositories do not inherit them as
     * direct requirements automatically.
     *
     * @var array<int, string>
     */
    private const array PACKAGED_UNUSED_DEPENDENCIES = [
        'ergebnis/composer-normalize',
        'fakerphp/faker',
        'fast-forward/phpdoc-bootstrap-template',
        'php-parallel-lint/php-parallel-lint',
        'phpdocumentor/shim',
        'phpmetrics/phpmetrics',
        'phpro/grumphp-shim',
        'pyrech/composer-changelogs',
        'rector/jack',
        'saggre/phpdocumentor-markdown',
        'symfony/var-dumper',
    ];

    /**
     * Production dependencies intentionally kept in require for the packaged toolchain.
     *
     * These dependencies are only exercised in test and development paths inside
     * this repository, but they MUST remain available to the packaged DevTools
     * runtime for consumer projects that choose to use those capabilities.
     *
     * @var array<int, string>
     */
    private const array PACKAGED_PROD_ONLY_IN_DEV_DEPENDENCIES = [
        'phpspec/prophecy',
        'phpspec/prophecy-phpunit',
        'symfony/var-exporter',
    ];

    /**
     * Creates the default Composer Dependency Analyser configuration.
     *
     * @param callable|null $customize optional callback to customize the configuration
     *
     * @return Configuration the configured analyser configuration
     */
    public static function configure(?callable $customize = null): Configuration
    {
        $configuration = new Configuration();

        if (self::isDevToolsRepository(__DIR__)) {
            self::configurePackagedRepositoryIgnores($configuration);
        }

        if (null !== $customize) {
            $customize($configuration);
        }

        return $configuration;
    }

    /**
     * Applies the ignores required only by the packaged DevTools repository.
     *
     * @param Configuration $configuration the analyser configuration to customize
     *
     * @return void
     */
    private static function configurePackagedRepositoryIgnores(Configuration $configuration): void
    {
        $configuration->ignoreErrorsOnExtension('ext-pcntl', [ErrorType::SHADOW_DEPENDENCY]);
        $configuration->ignoreErrorsOnPackages(self::PACKAGED_UNUSED_DEPENDENCIES, [ErrorType::UNUSED_DEPENDENCY]);
        $configuration->ignoreErrorsOnPackages(
            self::PACKAGED_PROD_ONLY_IN_DEV_DEPENDENCIES,
            [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV],
        );
    }

    /**
     * Detects whether the analyser is running inside the DevTools repository itself.
     *
     * @param string $configDirectory the directory where the config class is loaded from
     *
     * @return bool true when the config is loaded from the repository checkout itself
     */
    private static function isDevToolsRepository(string $configDirectory): bool
    {
        return ! self::isInstalledAsDependency($configDirectory);
    }

    /**
     * Detects whether the packaged config is being loaded from a consumer vendor directory.
     *
     * @param string $configDirectory the directory where the config class is loaded from
     *
     * @return bool true when DevTools is being used from vendor/fast-forward/dev-tools
     */
    private static function isInstalledAsDependency(string $configDirectory): bool
    {
        return str_contains($configDirectory, self::VENDOR_PACKAGE_PATH);
    }
}
