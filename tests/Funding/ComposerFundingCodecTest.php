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

namespace FastForward\DevTools\Tests\Funding;

use FastForward\DevTools\Funding\ComposerFundingCodec;
use FastForward\DevTools\Funding\FundingProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function Safe\json_decode;

#[CoversClass(ComposerFundingCodec::class)]
#[UsesClass(FundingProfile::class)]
final class ComposerFundingCodecTest extends TestCase
{
    #[Test]
    public function parseWillNormalizeSupportedAndUnsupportedFundingEntries(): void
    {
        $codec = new ComposerFundingCodec();

        $profile = $codec->parse(<<<'JSON'
{
  "name": "example/package",
  "funding": [
    {"type": "github", "url": "https://github.com/sponsors/foo"},
    {"type": "custom", "url": "https://example.com/support"},
    {"type": "patreon", "url": "https://patreon.com/example"}
  ]
}
JSON);

        self::assertSame(['foo'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
        self::assertSame(
            [['type' => 'patreon', 'url' => 'https://patreon.com/example']],
            $profile->getUnsupportedComposerEntries(),
        );
    }

    #[Test]
    public function dumpWillWriteSupportedFundingAndPreserveUnsupportedEntries(): void
    {
        $codec = new ComposerFundingCodec();

        $contents = $codec->dump(
            '{"name":"example/package"}',
            new FundingProfile(
                ['bar'],
                ['https://example.com/support'],
                unsupportedComposerEntries: [['type' => 'patreon', 'url' => 'https://patreon.com/example']],
            ),
        );

        $decoded = json_decode($contents, true);

        self::assertSame(
            [
                ['type' => 'github', 'url' => 'https://github.com/sponsors/bar'],
                ['type' => 'custom', 'url' => 'https://example.com/support'],
                ['type' => 'patreon', 'url' => 'https://patreon.com/example'],
            ],
            $decoded['funding'],
        );
    }
}
