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

namespace FastForward\DevTools\Tests\Changelog\Conflict;

use FastForward\DevTools\Changelog\Conflict\UnreleasedChangelogConflictResolver;
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Changelog\Renderer\MarkdownRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(UnreleasedChangelogConflictResolver::class)]
#[UsesClass(ChangelogDocument::class)]
#[UsesClass(ChangelogEntryType::class)]
#[UsesClass(ChangelogRelease::class)]
final class UnreleasedChangelogConflictResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChangelogParserInterface>
     */
    private ObjectProphecy $parser;

    /**
     * @var ObjectProphecy<MarkdownRendererInterface>
     */
    private ObjectProphecy $renderer;

    private UnreleasedChangelogConflictResolver $resolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->parser = $this->prophesize(ChangelogParserInterface::class);
        $this->renderer = $this->prophesize(MarkdownRendererInterface::class);
        $this->resolver = new UnreleasedChangelogConflictResolver(
            $this->parser->reveal(),
            $this->renderer->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillPreserveBothSidesOfUnreleasedDrift(): void
    {
        $target = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Fixed, 'Keep main fix'),
        ]);
        $source = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Fixed, 'Keep branch fix'),
        ]);

        $this->parser->parse('main changelog')
            ->willReturn($target)
            ->shouldBeCalledOnce();
        $this->parser->parse('branch changelog')
            ->willReturn($source)
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static fn(ChangelogDocument $document): bool => [
                'Keep main fix',
                'Keep branch fix',
            ] === $document->getUnreleased()
                ->getEntriesFor(ChangelogEntryType::Fixed)),
            'https://github.com/php-fast-forward/dev-tools',
        )->willReturn('resolved changelog')
            ->shouldBeCalledOnce();

        self::assertSame(
            'resolved changelog',
            $this->resolver->resolve(
                'main changelog',
                ['branch changelog'],
                'https://github.com/php-fast-forward/dev-tools',
            ),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillKeepBranchEntriesInCurrentUnreleasedAfterAReleaseMovedMain(): void
    {
        $target = new ChangelogDocument([
            new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION),
            (new ChangelogRelease('1.2.0', '2026-04-24'))
                ->withEntry(ChangelogEntryType::Changed, 'Already released main entry'),
        ]);
        $source = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Changed, 'Already released main entry')
                ->withEntry(ChangelogEntryType::Changed, 'Keep branch-only entry'),
        ]);

        $this->parser->parse('released main changelog')
            ->willReturn($target)
            ->shouldBeCalledOnce();
        $this->parser->parse('branch changelog')
            ->willReturn($source)
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static function (ChangelogDocument $document): bool {
                $unreleased = $document->getUnreleased();
                $release = $document->getRelease('1.2.0');

                return ['Keep branch-only entry'] === $unreleased->getEntriesFor(ChangelogEntryType::Changed)
                    && $release instanceof ChangelogRelease
                    && ['Already released main entry'] === $release->getEntriesFor(ChangelogEntryType::Changed);
            }),
            null,
        )->willReturn('resolved changelog')
            ->shouldBeCalledOnce();

        self::assertSame(
            'resolved changelog',
            $this->resolver->resolve('released main changelog', ['branch changelog']),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillDeduplicateEntriesFromMultipleSources(): void
    {
        $target = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Added, 'Keep main addition'),
        ]);
        $firstSource = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Added, 'Keep branch addition'),
        ]);
        $secondSource = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Added, 'Keep branch addition'),
        ]);

        $this->parser->parse('main changelog')
            ->willReturn($target)
            ->shouldBeCalledOnce();
        $this->parser->parse('first branch changelog')
            ->willReturn($firstSource)
            ->shouldBeCalledOnce();
        $this->parser->parse('second branch changelog')
            ->willReturn($secondSource)
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static fn(ChangelogDocument $document): bool => [
                'Keep main addition',
                'Keep branch addition',
            ] === $document->getUnreleased()
                ->getEntriesFor(ChangelogEntryType::Added)),
            null,
        )->willReturn('resolved changelog')
            ->shouldBeCalledOnce();

        self::assertSame(
            'resolved changelog',
            $this->resolver->resolve('main changelog', ['first branch changelog', 'second branch changelog']),
        );
    }
}
