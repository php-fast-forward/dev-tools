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

namespace FastForward\DevTools\Tests\Changelog\Renderer;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Renderer\MarkdownRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkdownRenderer::class)]
#[UsesClass(ChangelogDocument::class)]
#[UsesClass(ChangelogRelease::class)]
#[UsesClass(ChangelogEntryType::class)]
final class MarkdownRendererTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function renderWillGenerateChangelogWithHeaderAndUnreleasedSection(): void
    {
        $output = (new MarkdownRenderer())->render(ChangelogDocument::create());

        self::assertStringStartsWith('# Changelog', $output);
        self::assertStringContainsString('## [' . ChangelogDocument::UNRELEASED_VERSION . ']', $output);
        self::assertStringNotContainsString("## [Unreleased]\n\n\n", $output);
        self::assertStringEndsWith("\n", $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillIncludePublishedSectionsAndReferences(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                ChangelogEntryType::Changed,
                'Pending change'
            ),
            (new ChangelogRelease('1.1.0', '2026-04-02'))->withEntry(ChangelogEntryType::Changed, 'Feature B'),
            (new ChangelogRelease('1.0.0', '2026-04-01'))->withEntry(ChangelogEntryType::Added, 'Feature A'),
        ]);

        $output = (new MarkdownRenderer())->render($document, 'git@github.com:php-fast-forward/dev-tools.git');

        self::assertStringContainsString('## [1.1.0] - 2026-04-02', $output);
        self::assertStringContainsString('### Added', $output);
        self::assertStringContainsString('### Changed', $output);
        self::assertStringContainsString(
            '[unreleased]: https://github.com/php-fast-forward/dev-tools/compare/v1.1.0...HEAD',
            $output,
        );
        self::assertStringContainsString(
            '[1.1.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.0...v1.1.0',
            $output,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function renderReleaseBodyWillOmitTheReleaseHeading(): void
    {
        $release = (new ChangelogRelease('1.2.0', '2026-04-19'))
            ->withEntry(ChangelogEntryType::Added, 'Ship changelog automation');

        $output = (new MarkdownRenderer())->renderReleaseBody($release);

        self::assertStringNotContainsString('## [1.2.0]', $output);
        self::assertStringContainsString('### Added', $output);
        self::assertStringContainsString('- Ship changelog automation', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillOmitDanglingReleaseDateSeparatorsWhenDateIsMissing(): void
    {
        $document = new ChangelogDocument([
            new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION),
            (new ChangelogRelease('1.2.0'))->withEntry(ChangelogEntryType::Added, 'Ship changelog automation'),
        ]);

        $output = (new MarkdownRenderer())->render($document);

        self::assertStringContainsString('## [1.2.0]', $output);
        self::assertStringNotContainsString('## [1.2.0] - ', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillNotInsertExtraBlankLinesBetweenRenderedSections(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease('1.2.0', '2026-04-19'))
                ->withEntry(ChangelogEntryType::Added, 'Add changelog automation')
                ->withEntry(ChangelogEntryType::Fixed, 'Preserve release sections'),
        ]);

        $output = (new MarkdownRenderer())->render($document);

        self::assertStringContainsString("### Added\n\n- Add changelog automation\n\n### Fixed", $output);
        self::assertStringNotContainsString("\n\n\n### Fixed", $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillKeepOnlyOneBlankLineBetweenAnEmptyUnreleasedSectionAndTheNextRelease(): void
    {
        $document = new ChangelogDocument([
            new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION),
            (new ChangelogRelease('1.2.0', '2026-04-19'))->withEntry(
                ChangelogEntryType::Added,
                'Ship changelog automation',
            ),
        ]);

        $output = (new MarkdownRenderer())->render($document);

        self::assertStringContainsString("## [Unreleased]\n\n## [1.2.0] - 2026-04-19", $output);
        self::assertStringNotContainsString("## [Unreleased]\n\n\n## [1.2.0] - 2026-04-19", $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillOmitReferencesWhenRepositoryUrlIsMissingOrBlank(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease('1.2.0', '2026-04-19'))->withEntry(
                ChangelogEntryType::Added,
                'Ship changelog automation',
            ),
        ]);

        $renderer = new MarkdownRenderer();

        self::assertStringNotContainsString('[1.2.0]:', $renderer->render($document));
        self::assertStringNotContainsString('[1.2.0]:', $renderer->render($document, '   '));
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillNormalizeSshRepositoryUrlsAndTrimTrailingGitSuffix(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease('1.2.0', '2026-04-19'))->withEntry(
                ChangelogEntryType::Added,
                'Ship changelog automation',
            ),
        ]);

        $output = (new MarkdownRenderer())->render($document, 'ssh://git@github.com/php-fast-forward/dev-tools.git');

        self::assertStringContainsString(
            '[unreleased]: https://github.com/php-fast-forward/dev-tools/compare/v1.2.0...HEAD',
            $output,
        );
        self::assertStringContainsString(
            '[1.2.0]: https://github.com/php-fast-forward/dev-tools/releases/tag/v1.2.0',
            $output,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillOmitReferencesWhenOnlyUnreleasedSectionExists(): void
    {
        $output = (new MarkdownRenderer())->render(
            new ChangelogDocument([
                (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                    ChangelogEntryType::Added,
                    'Pending change',
                ),
            ]),
            'https://github.com/php-fast-forward/dev-tools'
        );

        self::assertStringNotContainsString('[unreleased]:', $output);
        self::assertStringNotContainsString('releases/tag', $output);
    }
}
