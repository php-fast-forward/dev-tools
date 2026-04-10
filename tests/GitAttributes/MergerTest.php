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

namespace FastForward\DevTools\Tests\GitAttributes;

use FastForward\DevTools\GitAttributes\Merger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Merger::class)]
final class MergerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function mergeWillCreateManagedBlockWhenFileIsEmpty(): void
    {
        $merger = new Merger();

        $result = $merger->merge('', ['/docs/', '/README.md']);

        self::assertSame("/docs/ export-ignore\n" . '/README.md export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillPreserveCustomEntries(): void
    {
        $merger = new Merger();
        $existingContent = implode("\n", ['*.zip -diff', '*.phar binary']);

        $result = $merger->merge($existingContent, ['/docs/']);

        self::assertSame("*.zip -diff\n*.phar binary\n" . '/docs/ export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillDeduplicateExistingAndGeneratedEntries(): void
    {
        $merger = new Merger();
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

        $result = $merger->merge($existingContent, [
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
        $merger = new Merger();
        $existingContent = implode("\n", [
            '*                   text=auto',
            '/.gitignore         export-ignore',
            'AGENTS.md           export-ignore',
            '/README.md export-ignore',
        ]);

        $result = $merger->merge($existingContent, ['/README.md'], ['/.gitignore', '/AGENTS.md']);

        self::assertSame("* text=auto\n" . '/README.md export-ignore', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergeWillSortExportIgnoreEntriesWithDirectoriesBeforeFiles(): void
    {
        $merger = new Merger();
        $existingContent = implode("\n", [
            '* text=auto',
            '/README.md export-ignore',
            '/docs/ export-ignore',
            '/AGENTS.md export-ignore',
        ]);

        $result = $merger->merge($existingContent, ['/.vscode/', '/tests/', '/.gitattributes']);

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
}
