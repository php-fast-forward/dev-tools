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

namespace FastForward\DevTools\Tests\GitAttributes;

use FastForward\DevTools\GitAttributes\ExportIgnoreFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportIgnoreFilter::class)]
final class ExportIgnoreFilterTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function filterWillRemoveConfiguredPathsEvenWithSlashVariants(): void
    {
        $filter = new ExportIgnoreFilter();

        $result = $filter->filter(
            ['/.github/', '/tests/', '/README.md', '/phpunit.xml.dist'],
            ['.github', '/tests', 'README.md/', ''],
        );

        self::assertSame(['/phpunit.xml.dist'], $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function filterWillPreserveOriginalCandidateOrder(): void
    {
        $filter = new ExportIgnoreFilter();

        $result = $filter->filter(['/docs/', '/.github/', '/README.md'], ['/README.md']);

        self::assertSame(['/docs/', '/.github/'], $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function filterWillKeepNestedCandidatesWhenParentPathIsConfigured(): void
    {
        $filter = new ExportIgnoreFilter();

        $result = $filter->filter(['/.agents/agents/', '/.agents/skills/', '/tests/'], ['/.agents/']);

        self::assertSame(['/tests/'], $result);
    }
}
