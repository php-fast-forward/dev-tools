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

use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use FastForward\DevTools\Config\RectorConfig;

return RectorConfig::configure(static function (Rector\Config\RectorConfig $rectorConfig): void {
    // Keep the legacy classifier constructor contract retained by the Git rule precedence fix.
    $rectorConfig->skip([
        RemoveUnusedPromotedPropertyRector::class => [__DIR__ . '/src/GitIgnore/Merger.php'],
        RemoveEmptyClassMethodRector::class => [__DIR__ . '/src/GitIgnore/Merger.php'],
    ]);
});
