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

use FastForward\DevTools\GitAttributes\Merger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Merger::class)]
final class MergerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function mergeWillCreateMissingDefaultsInProvidedOrder(): void
    {
        self::assertSame(
            "/README.md export-ignore\n/docs/ export-ignore\n/tests/ export-ignore",
            (new Merger())->merge('', ['/README.md', '/docs/', '/tests/', '/docs/']),
        );
    }

    /**
     * @param string $content
     */
    #[Test]
    #[DataProvider('orderedRules')]
    public function mergeWithoutGeneratedRulesPreservesConsumerPrecedence(string $content): void
    {
        $merger = new Merger();

        self::assertSame($content, $merger->merge($content, []));
        self::assertSame($content, $merger->merge($merger->merge($content, []), []));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function orderedRules(): iterable
    {
        yield 'explicit unset exception' => ["*.txt export-ignore\nkeep.txt -export-ignore"];
        yield 'explicit unspecified exception' => ["*.txt export-ignore\nkeep.txt !export-ignore"];
        yield 'repeated positive after exception' => ["*.txt export-ignore\nkeep.txt -export-ignore\n*.txt export-ignore"];
        yield 'repeated custom attributes' => ["* text=auto\n*.txt -text\n* text=auto"];
        yield 'overlapping patterns' => ["docs/** export-ignore\ndocs/keep.* -export-ignore\ndocs/keep.txt export-ignore"];
        yield 'comments and whitespace' => ["# comment\n/docs/    export-ignore\n/tests/  export-ignore"];
        yield 'quoted path' => ['"docs with spaces/file.txt" export-ignore'];
    }

    /**
     * @return void
     */
    #[Test]
    public function mergePlacesGeneratedDefaultsBeforeConsumerOverrides(): void
    {
        $content = "keep.txt -export-ignore\nother.txt !export-ignore\n*.zip -diff";
        $merger = new Merger();
        $result = $merger->merge($content, ['*.txt']);

        self::assertSame("*.txt export-ignore\n" . $content, $result);
        self::assertSame($result, $merger->merge($result, ['*.txt']));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergePreservesExistingPositiveRulesInsteadOfAddingDuplicates(): void
    {
        $content = "/docs/   export-ignore\n/docs/ -export-ignore\n/docs/ export-ignore";

        self::assertSame($content, (new Merger())->merge($content, ['/docs/', '/docs/']));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeRemovesSimplePositiveRulesForExplicitlyKeptPaths(): void
    {
        $content = "* text=auto\n/.agents/agents/ export-ignore\n/.agents/skills/ export-ignore\nAGENTS.md export-ignore\n/tests/ export-ignore";

        self::assertSame(
            "* text=auto\n/tests/ export-ignore",
            (new Merger())->merge($content, ['/.agents/agents/', '/AGENTS.md', '/tests/'], ['/.agents/', '/AGENTS.md']),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeKeepsAttributeOverridesForExplicitlyKeptPaths(): void
    {
        $content = "/docs/ -export-ignore\n/docs/ !export-ignore";

        self::assertSame($content, (new Merger())->merge($content, ['/docs/'], ['/docs/']));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeDoesNotConflateRootAnchoredAndUnanchoredGlobs(): void
    {
        self::assertSame(
            "/*.txt export-ignore\n*.txt export-ignore",
            (new Merger())->merge('*.txt export-ignore', ['/*.txt']),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeIgnoresBlankGeneratedAndKeepEntries(): void
    {
        self::assertSame('/docs/ export-ignore', (new Merger())->merge('', ['', ' ', '/docs/'], ['', ' ']));
    }
}
