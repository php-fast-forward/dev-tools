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

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\MarkdownRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkdownRenderer::class)]
final class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillGenerateChangelogWithHeader(): void
    {
        $output = $this->renderer->render([]);

        self::assertStringStartsWith('# Changelog', $output);
        self::assertStringContainsString(
            'All notable changes to this project will be documented in this file',
            $output
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillIncludeUnreleasedSection(): void
    {
        $output = $this->renderer->render([]);

        self::assertStringContainsString('## Unreleased - TBD', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillIncludeAllSectionTypes(): void
    {
        $output = $this->renderer->render([]);

        self::assertStringContainsString('### Added', $output);
        self::assertStringContainsString('### Changed', $output);
        self::assertStringContainsString('### Deprecated', $output);
        self::assertStringContainsString('### Removed', $output);
        self::assertStringContainsString('### Fixed', $output);
        self::assertStringContainsString('### Security', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillIncludeReleaseDataInReverseChronologicalOrder(): void
    {
        $releases = [
            [
                'version' => '1.0.0',
                'date' => '2026-04-01',
                'entries' => [
                    'Added' => ['Feature A'],
                ],
            ],
            [
                'version' => '0.9.0',
                'date' => '2026-03-01',
                'entries' => [
                    'Added' => ['Feature B'],
                ],
            ],
        ];

        $output = $this->renderer->render($releases);

        self::assertStringContainsString('## 0.9.0 - 2026-03-01', $output);
        self::assertStringContainsString('## 1.0.0 - 2026-04-01', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillIncludeEntriesForSections(): void
    {
        $releases = [
            [
                'version' => '1.0.0',
                'date' => '2026-04-01',
                'entries' => [
                    'Added' => ['New feature'],
                    'Fixed' => ['Bug fix'],
                ],
            ],
        ];

        $output = $this->renderer->render($releases);

        self::assertStringContainsString('- New feature', $output);
        self::assertStringContainsString('- Bug fix', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillShowNothingWhenNoEntriesForReleasedVersion(): void
    {
        $releases = [
            [
                'version' => '1.0.0',
                'date' => '2026-04-01',
                'entries' => [],
            ],
        ];

        $output = $this->renderer->render($releases);

        self::assertStringContainsString('- Nothing.', $output);
    }
}
