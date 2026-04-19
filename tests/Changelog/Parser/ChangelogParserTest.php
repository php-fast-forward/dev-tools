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

namespace FastForward\DevTools\Tests\Changelog\Parser;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Parser\ChangelogParser;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangelogParser::class)]
#[UsesClass(ChangelogDocument::class)]
#[UsesClass(ChangelogRelease::class)]
#[UsesClass(ChangelogEntryType::class)]
final class ChangelogParserTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function parseWillExtractReleaseSectionsAndEntries(): void
    {
        $document = (new ChangelogParser())->parse(<<<'MD'
            # Changelog

            All notable changes to this project will be documented in this file.

            The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
            and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

            ## [Unreleased]

            ### Added

            - Add release preparation workflow

            ### Fixed

            - Correct changelog checks

            ## [1.0.0] - 2026-04-01

            ### Added

            - Initial release
            MD);

        self::assertSame(ChangelogDocument::UNRELEASED_VERSION, $document->getUnreleased()->getVersion());
        self::assertSame(['Add release preparation workflow'], $document->getUnreleased()->getEntries()['Added']);
        self::assertSame('2026-04-01', $document->getRelease('1.0.0')?->getDate());
    }
}
