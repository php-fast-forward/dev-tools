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

namespace FastForward\DevTools\Tests\GitIgnore;

use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\Merger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Merger::class)]
#[UsesClass(GitIgnore::class)]
final class MergerTest extends TestCase
{
    /**
     * @param list<string> $entries
     */
    #[Test]
    #[DataProvider('orderedRules')]
    public function mergeWithEmptyCanonicalPreservesConsumerRules(array $entries): void
    {
        $result = (new Merger())->merge(new GitIgnore('/canonical', []), new GitIgnore('/project', $entries));

        self::assertSame($entries, $result->entries());
        self::assertSame('/project', $result->path());
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function orderedRules(): iterable
    {
        yield 'negation' => [['*.log', '!important.log']];
        yield 'repeated rule after exception' => [['*.log', '!important.log', '*.log']];
        yield 'directory traversal exception' => [['build/*', '!build/keep/', 'build/keep/*', '!build/keep/file.txt']];
        yield 'significant whitespace and escaped markers' => [[' leading', 'trailing\\ ', '\\#literal', '\\!literal', '0']];
    }

    /**
     * @return void
     */
    #[Test]
    public function mergePlacesCanonicalDefaultsBeforeConsumerOverrides(): void
    {
        $canonical = new GitIgnore('/canonical', ['*.log', 'build/*']);
        $project = new GitIgnore('/project', ['!important.log', '!build/keep/']);
        $merger = new Merger();

        $result = $merger->merge($canonical, $project);

        self::assertSame(['*.log', 'build/*', '!important.log', '!build/keep/'], $result->entries());
        self::assertSame($result->entries(), $merger->merge($canonical, $result)->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergePreservesExistingOccurrencesAfterCanonicalPrefix(): void
    {
        $canonical = new GitIgnore('/canonical', ['*.log']);
        $project = new GitIgnore('/project', ['!important.log', '*.log']);
        $merger = new Merger();

        $result = $merger->merge($canonical, $project);

        self::assertSame(['*.log', '!important.log', '*.log'], $result->entries());
        self::assertSame($result->entries(), $merger->merge($canonical, $result)->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeRemovesOnlyCommentsAndBlankLines(): void
    {
        $result = (new Merger())->merge(
            new GitIgnore('/canonical', ['', '# comment', 'vendor/']),
            new GitIgnore('/project', ['  ', '# another comment', '*.log', ' #literal']),
        );

        self::assertSame(['vendor/', '*.log', ' #literal'], $result->entries());
    }
}
