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

use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;

use function Safe\getcwd;

/**
 * Base ECS configuration for dev-tools.
 *
 * This configuration can be extended in consumer projects using one of the following patterns:
 *
 * 1. Use directly (recommended):
 *    ```php
 *    return require __DIR__ . '/vendor/fast-forward/dev-tools/ecs.php';
 *    ```
 *
 * 2. Extend with custom rules:
 *    ```php
 *    $builder = require __DIR__ . '/vendor/fast-forward/dev-tools/ecs.php';
 *    return $builder->withRules([CustomRule::class]);
 *    ```
 *
 * 3. Using the factory (recommended for advanced use cases):
 *    ```php
 *    use FastForward\DevTools\Config\EcsConfigFactory;
 *
 *    return EcsConfigFactory::create();
 *    ```
 *
 * @return ECSConfigBuilder the pre-configured ECS configuration builder
 */
$cwd = getcwd();

return ECSConfig::configure()
    ->withPaths([$cwd])
    ->withSkip([
        $cwd . '/public',
        $cwd . '/resources',
        $cwd . '/vendor',
        $cwd . '/tmp',
        PhpdocToCommentFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
        NoEmptyPhpdocFixer::class,
        PhpdocNoEmptyReturnFixer::class,
        GlobalNamespaceImportFixer::class,
        GeneralPhpdocAnnotationRemoveFixer::class,
    ])
    ->withRootFiles()
    ->withPhpCsFixerSets(symfony: true, symfonyRisky: true, auto: true, autoRisky: true)
    ->withPreparedSets(psr12: true, common: true, symplify: true, strict: true, cleanCode: true)
    ->withConfiguredRule(PhpdocAlignFixer::class, [
        'align' => 'left',
    ])
    ->withConfiguredRule(PhpUnitTestCaseStaticMethodCallsFixer::class, [
        'call_type' => 'self',
    ])
    ->withConfiguredRule(PhpdocAddMissingParamAnnotationFixer::class, [
        'only_untyped' => false,
    ]);
