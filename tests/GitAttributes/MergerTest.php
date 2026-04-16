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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Merger::class)]
final class MergerTest extends TestCase
{
    private Merger $merger;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->merger = new Merger();
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillCreateManagedBlockWhenFileIsEmpty(): void
    {
        $result = $this->merger->merge('', ['/docs/', '/README.md']);

        self::assertSame("/docs/ export-ignore\n" . '/README.md export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillPreserveCustomEntries(): void
    {
        $existingContent = implode("\n", ['*.zip -diff', '*.phar binary']);

        $result = $this->merger->merge($existingContent, ['/docs/']);

        self::assertSame("*.zip -diff\n*.phar binary\n" . '/docs/ export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillDeduplicateExistingAndGeneratedEntries(): void
    {
        $existingContent = implode("\n", [
            '*                   text=auto',
            '/.github/           export-ignore',
            '/docs/              export-ignore',
            '/tests/             export-ignore',
            '/.gitattributes     export-ignore',
            '/.gitignore         export-ignore',
            '/.gitmodules        export-ignore',
            'AGENTS.md           export-ignore',
            '/.github/ export-ignore',
            '/.vscode/ export-ignore',
            '/docs/ export-ignore',
            '/tests/ export-ignore',
            '/.editorconfig export-ignore',
            '/.gitattributes export-ignore',
            '/.gitignore export-ignore',
            '/.gitmodules export-ignore',
            '/README.md export-ignore',
        ]);

        $result = $this->merger->merge($existingContent, [
            '/.github/',
            '/.vscode/',
            '/docs/',
            '/tests/',
            '/.editorconfig',
            '/.gitattributes',
            '/.gitignore',
            '/.gitmodules',
            '/README.md',
        ]);

        self::assertSame(
            "* text=auto\n"
            . "/.github/ export-ignore\n"
            . "/.vscode/ export-ignore\n"
            . "/docs/ export-ignore\n"
            . "/tests/ export-ignore\n"
            . "/.editorconfig export-ignore\n"
            . "/.gitattributes export-ignore\n"
            . "/.gitignore export-ignore\n"
            . "/.gitmodules export-ignore\n"
            . "AGENTS.md export-ignore\n"
            . '/README.md export-ignore',
            $result,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillRemoveExistingExportIgnoreRulesForKeptPaths(): void
    {
        $existingContent = implode("\n", [
            '*                   text=auto',
            '/.gitignore         export-ignore',
            'AGENTS.md           export-ignore',
            '/README.md export-ignore',
        ]);

        $result = $this->merger->merge($existingContent, ['/README.md'], ['/.gitignore', '/AGENTS.md']);

        self::assertSame("* text=auto\n" . '/README.md export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillSortExportIgnoreEntriesWithDirectoriesBeforeFiles(): void
    {
        $existingContent = implode("\n", [
            '* text=auto',
            '/README.md export-ignore',
            '/docs/ export-ignore',
            '/AGENTS.md export-ignore',
        ]);

        $result = $this->merger->merge($existingContent, ['/.vscode/', '/tests/', '/.gitattributes']);

        self::assertSame(
            "* text=auto\n"
            . "/.vscode/ export-ignore\n"
            . "/docs/ export-ignore\n"
            . "/tests/ export-ignore\n"
            . "/.gitattributes export-ignore\n"
            . "/AGENTS.md export-ignore\n"
            . '/README.md export-ignore',
            $result,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillPreserveComments(): void
    {
        $existingContent = "# This is a comment\n*.zip -diff";

        $result = $this->merger->merge($existingContent, ['/docs/']);

        self::assertStringStartsWith('#', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillNormalizeWhitespaceInExistingEntries(): void
    {
        $existingContent = '/docs/   export-ignore';

        $result = $this->merger->merge($existingContent, ['/tests/']);

        self::assertStringContainsString('/docs/ export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillDeduplicateNormalizedEntries(): void
    {
        $existingContent = '/.github/ export-ignore';

        $result = $this->merger->merge($existingContent, ['/.github/']);

        self::assertCount(1, explode("\n", trim($result)));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWithEmptyExistingContentWillCreateCleanOutput(): void
    {
        $result = $this->merger->merge('', ['/docs/', '/tests/']);

        self::assertSame("/docs/ export-ignore\n/tests/ export-ignore", $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillHandleGlobPatternsWithExportIgnore(): void
    {
        $existingContent = '*.pdf export-ignore';

        $result = $this->merger->merge($existingContent, ['/docs/']);

        self::assertStringContainsString('*.pdf export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillSortDirectoriesBeforeFiles(): void
    {
        $result = $this->merger->merge('', ['/README.md', '/docs/', '/tests/', '/.editorconfig']);

        $lines = explode("\n", $result);
        self::assertSame('/docs/ export-ignore', $lines[0]);
        self::assertSame('/tests/ export-ignore', $lines[1]);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillHandleMultipleWhitespaceNormalize(): void
    {
        $existingContent = "/docs/    export-ignore\n/tests/  export-ignore";

        $result = $this->merger->merge($existingContent, []);

        self::assertStringContainsString('/docs/ export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillRemoveDuplicateNonExportIgnoreLines(): void
    {
        $existingContent = "* text=auto\n* text=auto";

        $result = $this->merger->merge($existingContent, []);

        self::assertSame(1, substr_count($result, '* text=auto'));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillPreserveLeadingWhitespaceInCustomEntries(): void
    {
        $existingContent = '  * text=auto';

        $result = $this->merger->merge($existingContent, []);

        self::assertStringContainsString('* text=auto', $result);
    }
}
