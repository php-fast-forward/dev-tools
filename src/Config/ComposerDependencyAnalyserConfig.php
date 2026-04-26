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

use FastForward\DevTools\Path\DevToolsPathResolver;
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
    public const string ENV_SHOW_SHADOW_DEPENDENCIES = 'FAST_FORWARD_DEV_TOOLS_SHOW_SHADOW_DEPENDENCIES';

    /**
     * Dependencies that are only required by the packaged DevTools distribution itself.
     *
     * These packages MUST only be ignored when the analyser runs against the
     * DevTools repository, because consumer repositories do not inherit them as
     * direct requirements automatically.
     *
     * @var array<int, string>
     */
    public const array DEFAULT_PACKAGED_UNUSED_DEPENDENCIES = [
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
    public const array DEFAULT_PACKAGED_PROD_ONLY_IN_DEV_DEPENDENCIES = [
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

        if (! self::shouldShowShadowDependencies()) {
            self::applyIgnoresShadowDependencies($configuration);
        }

        if (DevToolsPathResolver::isRepositoryCheckout()) {
            self::applyPackagedRepositoryIgnores($configuration);
        }

        if (null !== $customize) {
            $customize($configuration);
        }

        return $configuration;
    }

    /**
     * The default configuration ignores shadow dependencies because Fast
     * Forward packages MAY intentionally require dependency groups. For example,
     * ecosystem or meta packages can require related PSR or framework packages
     * so consumers do not need to install every package one by one.
     *
     * @param Configuration $configuration the analyser configuration to customize
     *
     * @return Configuration the modified configuration with shadow dependencies ignored
     */
    public static function applyIgnoresShadowDependencies(Configuration $configuration): Configuration
    {
        $configuration->ignoreErrors([ErrorType::SHADOW_DEPENDENCY]);

        return $configuration;
    }

    /**
     * Determines whether shadow dependency reports SHOULD remain visible.
     *
     * @return bool
     */
    public static function shouldShowShadowDependencies(): bool
    {
        return '1' === getenv(self::ENV_SHOW_SHADOW_DEPENDENCIES);
    }

    /**
     * Applies the ignores required only by the packaged DevTools repository.
     *
     * @param Configuration $configuration the analyser configuration to customize
     *
     * @return Configuration the modified configuration with packaged repository ignores applied
     */
    public static function applyPackagedRepositoryIgnores(Configuration $configuration): Configuration
    {
        $configuration->ignoreErrorsOnExtension('ext-pcntl', [ErrorType::SHADOW_DEPENDENCY]);
        $configuration->ignoreErrorsOnPackages(
            self::DEFAULT_PACKAGED_UNUSED_DEPENDENCIES,
            [ErrorType::UNUSED_DEPENDENCY]
        );
        $configuration->ignoreErrorsOnPackages(
            self::DEFAULT_PACKAGED_PROD_ONLY_IN_DEV_DEPENDENCIES,
            [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV],
        );
        $configuration->ignoreErrorsOnPackage('composer/composer', [ErrorType::DEV_DEPENDENCY_IN_PROD]);

        return $configuration;
    }
}
