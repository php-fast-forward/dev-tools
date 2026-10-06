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

use FastForward\DevTools\Path\WorkingProjectPathResolver;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use Symplify\CodingStandard\Fixer\Annotation\RemoveMethodNameDuplicateDescriptionFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemovePHPStormAnnotationFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemovePropertyVariableNameDescriptionFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\Commenting\ParamReturnAndVarTagMalformsFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\CodingStandard\Fixer\Spacing\SpaceAfterCommaHereNowDocFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;
use Symplify\CodingStandard\Fixer\Strict\BlankLineAfterStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig as ECSConfigInterface;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;
use function Safe\getcwd;

/**
 * Provides the default ECS configuration.
 *
 * Consumers can use this as a starting point and extend it:
 *
 *     $config = \FastForward\DevTools\Config\ECSConfig::configure();
 *     $config->withRules([CustomRule::class]);
 *     $config->withConfiguredRule(PhpdocAlignFixer::class, ['align' => 'vertical']);
 *     return $config;
 *
 * @see https://github.com/symplify/easy-coding-standard
 */
final class ECSConfig
{
    /**
     * @var array{symfony: bool, symfonyRisky: bool, auto: bool, autoRisky: bool} the PHP-CS-Fixer sets applied by default
     */
    public const array DEFAULT_PHP_CS_FIXER_SETS = [
        'symfony' => true,
        'symfonyRisky' => true,
        'auto' => true,
        'autoRisky' => true,
    ];

    /**
     * @var array{psr12: bool, common: bool, symplify: bool, cleanCode: bool} the prepared ECS sets applied by default
     */
    public const array DEFAULT_PREPARED_SETS = [
        'psr12' => true,
        'common' => true,
        'symplify' => true,
        'cleanCode' => true,
    ];

    /**
     * @var list<class-string> the ECS/CS Fixer rules that SHOULD be skipped by default
     */
    public const array DEFAULT_SKIPPED_RULES = [
        PhpdocToCommentFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
        NoEmptyPhpdocFixer::class,
        PhpdocNoEmptyReturnFixer::class,
        GlobalNamespaceImportFixer::class,
        GeneralPhpdocAnnotationRemoveFixer::class,
    ];

    /**
     * @var array<class-string, array<string, mixed>> the configured ECS rules applied by default
     */
    public const array DEFAULT_CONFIGURED_RULES = [
        PhpdocAlignFixer::class => [
            'align' => 'left',
        ],
        PhpUnitTestCaseStaticMethodCallsFixer::class => [
            'call_type' => 'self',
        ],
        PhpdocAddMissingParamAnnotationFixer::class => [
            'only_untyped' => false,
        ],
    ];

    /**
     * Creates the default ECS configuration.
     *
     * @param callable|null $customize optional callback to customize the configuration builder
     *
     * @return ECSConfigBuilder the configured ECS configuration builder
     */
    public static function configure(?callable $customize = null): ECSConfigBuilder
    {
        $workingDirectory = getcwd();
        $config = new ECSConfigBuilder();

        self::applyDefaultPathsAndSkips($config, $workingDirectory);
        self::applyDefaultRulesAndSets($config);

        if (null !== $customize) {
            $customize($config);
        }

        return $config;
    }

    /**
     * Applies the default repository paths and skipped rules to an ECS builder.
     *
     * @param ECSConfigBuilder $config
     * @param string $workingDirectory
     */
    public static function applyDefaultPathsAndSkips(
        ECSConfigBuilder $config,
        string $workingDirectory
    ): ECSConfigBuilder {
        $paths = WorkingProjectPathResolver::getToolingSourcePaths($workingDirectory);
        $skipPaths = WorkingProjectPathResolver::getToolingExcludedDirectories();

        return $config
            ->withPaths($paths)
            ->withSkip([...$skipPaths, ...self::DEFAULT_SKIPPED_RULES]);
    }

    /**
     * Applies the default ECS sets, root files, and configured rules to an ECS builder.
     *
     * @param ECSConfigBuilder $config
     */
    public static function applyDefaultRulesAndSets(ECSConfigBuilder $config): ECSConfigBuilder
    {
        $config
            ->withRootFiles()
            ->withPreparedSets(
                psr12: self::DEFAULT_PREPARED_SETS['psr12'],
                common: self::DEFAULT_PREPARED_SETS['common'],
                cleanCode: self::DEFAULT_PREPARED_SETS['cleanCode'],
            );

        $config->withSets([__DIR__ . '/../../resources/ecs/php-cs-fixer.php']);

        foreach (self::DEFAULT_CONFIGURED_RULES as $rule => $configuration) {
            $config->withConfiguredRule($rule, $configuration);
        }

        return $config;
    }

    /**
     * Registers PHP-CS-Fixer presets and the legacy Symplify set without deprecated ECS APIs.
     *
     * @param ECSConfigInterface $config
     */
    public static function applyPhpCsFixerSets(ECSConfigInterface $config): void
    {
        $ruleSet = new RuleSet([
            '@Symfony' => self::DEFAULT_PHP_CS_FIXER_SETS['symfony'],
            '@Symfony:risky' => self::DEFAULT_PHP_CS_FIXER_SETS['symfonyRisky'],
            '@auto' => self::DEFAULT_PHP_CS_FIXER_SETS['auto'],
            '@auto:risky' => self::DEFAULT_PHP_CS_FIXER_SETS['autoRisky'],
        ]);
        $fixerFactory = new FixerFactory();
        $fixerFactory->registerBuiltInFixers();
        $fixerFactory->useRuleSet($ruleSet);
        foreach ($fixerFactory->getFixers() as $fixer) {
            $configuration = $ruleSet->getRuleConfiguration($fixer->getName());

            if (null === $configuration) {
                $config->rule($fixer::class);
            } else {
                $config->ruleWithConfiguration($fixer::class, $configuration);
            }
        }

        if (self::DEFAULT_PREPARED_SETS['symplify']) {
            $config->rules([
                RemovePHPStormAnnotationFixer::class,
                ParamReturnAndVarTagMalformsFixer::class,
                RemoveUselessDefaultCommentFixer::class,
                RemoveMethodNameDuplicateDescriptionFixer::class,
                RemovePropertyVariableNameDescriptionFixer::class,
                ArrayListItemNewlineFixer::class,
                ArrayOpenerAndCloserNewlineFixer::class,
                StandaloneLinePromotedPropertyFixer::class,
                MethodChainingNewlineFixer::class,
                SpaceAfterCommaHereNowDocFixer::class,
                BlankLineAfterStrictTypesFixer::class,
                LineLengthFixer::class,
            ]);
            $config->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
                'annotations' => ['throws', 'author', 'package', 'group', 'covers', 'category'],
            ]);
        }
    }
}
