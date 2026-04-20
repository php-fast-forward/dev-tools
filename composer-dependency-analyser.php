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

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$configuration = new Configuration();

$unusedPackages = [
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
    'shipmonk/composer-dependency-analyser',
    'symfony/var-dumper',
];

foreach ($unusedPackages as $unusedPackage) {
    $configuration->ignoreErrorsOnPackage($unusedPackage, [ErrorType::UNUSED_DEPENDENCY]);
}

return $configuration
    ->ignoreErrorsOnExtension('ext-pcntl', [ErrorType::SHADOW_DEPENDENCY]);
