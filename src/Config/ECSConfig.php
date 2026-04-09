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
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;

use function Safe\getcwd;

/**
 * Provides the default ECS configuration.
 *
 * Consumers can use this as a starting point and extend it:
 *
 *     $config = \FastForward\DevTools\Config\ECSConfig::configure();
 *     $config->withRules([CustomRule::class]);
 *     $config->withConfiguredRule(PhpdocAlignFixer::class, ['align' => 'right']);
 *     return $config;
 *
 * @see https://github.com/symplify/easy-coding-standard
 */
final class ECSConfig
{
    /**
     * Creates the default ECS configuration.
     *
     * @return ECSConfigBuilder the configured ECS configuration builder
     */
    public static function configure(): ECSConfigBuilder
    {
        $cwd = getcwd();

        $config = new ECSConfigBuilder();

        return $config
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
