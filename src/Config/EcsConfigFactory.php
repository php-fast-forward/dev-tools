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
 * Factory for creating and extending the ECS (EasyCodingStandard) configuration.
 *
 * This factory allows consumers to build on top of the base configuration provided
 * by the dev-tools package instead of replacing it entirely. Consumers can extend
 * the configuration with custom rules, paths, skips, and other options while
 * preserving the ability to receive upstream updates automatically.
 *
 * Usage examples:
 *
 * 1. Using the factory method (recommended):
 *    ```php
 *    use FastForward\DevTools\Config\EcsConfigFactory;
 *
 *    return EcsConfigFactory::create();
 *    ```
 *
 * 2. Using the require pattern:
 *    ```php
 *    return require __DIR__ . '/vendor/fast-forward/dev-tools/ecs.php';
 *    ```
 *
 * 3. Extending the base configuration:
 *    ```php
 *    $builder = require __DIR__ . '/vendor/fast-forward/dev-tools/ecs.php';
 *    return $builder->withRules([CustomRule::class]);
 *    ```
 */
final class EcsConfigFactory
{
    /**
     * Creates and returns a pre-configured ECSConfigBuilder with the base dev-tools rules.
     *
     * This method returns the base configuration builder that consumers can further
     * customize with additional rules, paths, skips, or configured rules.
     *
     * @return ECSConfigBuilder the pre-configured ECS configuration builder
     */
    public static function create(): ECSConfigBuilder
    {
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
    }

    /**
     * Loads and returns the base ECS configuration from the dev-tools package.
     *
     * This method can be used to require the base configuration file directly,
     * providing full control over the configuration object.
     *
     * @param string|null $workingDirectory the working directory to use (defaults to cwd)
     *
     * @return ECSConfigBuilder the pre-configured ECS configuration builder
     */
    public static function loadBaseConfiguration(?string $workingDirectory = null): ECSConfigBuilder
    {
        $cwd = $workingDirectory ?? getcwd();

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
    }
}
