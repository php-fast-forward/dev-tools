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

namespace FastForward\DevTools\Tests\Config;

use FastForward\DevTools\Config\ECSConfig;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\ProjectPathResolver;

use function Safe\getcwd;

#[CoversClass(ECSConfig::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(ProjectPathResolver::class)]
final class ECSConfigTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function configureWillReturnECSConfigBuilder(): void
    {
        $result = ECSConfig::configure();

        self::assertInstanceOf(ECSConfigBuilder::class, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeReusableDefaultSkippedRules(): void
    {
        self::assertSame([
            PhpdocToCommentFixer::class,
            NoSuperfluousPhpdocTagsFixer::class,
            NoEmptyPhpdocFixer::class,
            PhpdocNoEmptyReturnFixer::class,
            GlobalNamespaceImportFixer::class,
            GeneralPhpdocAnnotationRemoveFixer::class,
        ], ECSConfig::DEFAULT_SKIPPED_RULES);
    }

    /**
     * @return void
     */
    #[Test]
    public function applyDefaultPathsAndSkipsWillKeepWorkingWithABuilder(): void
    {
        $builder = new ECSConfigBuilder();

        self::assertInstanceOf(ECSConfigBuilder::class, ECSConfig::applyDefaultPathsAndSkips($builder, getcwd()));
    }
}
