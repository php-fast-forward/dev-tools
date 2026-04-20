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
    /**
     * @return void
     */
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
            [[
                'type' => 'patreon',
                'url' => 'https://patreon.com/example',
            ]],
            $profile->getUnsupportedComposerEntries(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillWriteSupportedFundingAndPreserveUnsupportedEntries(): void
    {
        $codec = new ComposerFundingCodec();

        $contents = $codec->dump(
            '{"name":"example/package"}',
            new FundingProfile(
                ['bar'],
                ['https://example.com/support'],
                unsupportedComposerEntries: [[
                    'type' => 'patreon',
                    'url' => 'https://patreon.com/example',
                ]],
            ),
        );

        $decoded = json_decode($contents, true);

        self::assertSame(
            [
                [
                    'type' => 'github',
                    'url' => 'https://github.com/sponsors/bar',
                ],
                [
                    'type' => 'custom',
                    'url' => 'https://example.com/support',
                ],
                [
                    'type' => 'patreon',
                    'url' => 'https://patreon.com/example',
                ],
            ],
            $decoded['funding'],
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function parseWillIgnoreInvalidEntriesAndDeduplicateSupportedValues(): void
    {
        $codec = new ComposerFundingCodec();

        $profile = $codec->parse(<<<'JSON'
            {
              "name": "example/package",
              "funding": [
                "invalid",
                {"type": "github", "url": "https://github.com/sponsors/foo"},
                {"type": "github", "url": "https://www.github.com/sponsors/foo"},
                {"type": "github", "url": "https://github.com/foo"},
                {"type": "custom", "url": " https://example.com/support "},
                {"type": "custom", "url": "https://example.com/support"},
                {"type": "patreon", "url": ""},
                {"type": "other"}
              ]
            }
            JSON);

        self::assertSame(['foo'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
        self::assertSame(
            [
                [
                    'type' => 'github',
                    'url' => 'https://github.com/foo',
                ],
                ['type' => 'patreon', 'url' => ''],
                ['type' => 'other'],
            ],
            $profile->getUnsupportedComposerEntries(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function parseWillReturnEmptyProfileWhenFundingIsNotAnArray(): void
    {
        $codec = new ComposerFundingCodec();
        $profile = $codec->parse('{"name":"example/package","funding":"nope"}');

        self::assertSame([], $profile->getGithubSponsors());
        self::assertSame([], $profile->getCustomUrls());
        self::assertSame([], $profile->getUnsupportedComposerEntries());
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillRemoveFundingWhenTheProfileHasNoEntries(): void
    {
        $codec = new ComposerFundingCodec();

        $contents = $codec->dump(
            '{"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"}]}',
            new FundingProfile(),
        );

        $decoded = json_decode($contents, true);

        self::assertArrayNotHasKey('funding', $decoded);
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillInsertFundingImmediatelyAfterSupportWhenPresent(): void
    {
        $codec = new ComposerFundingCodec();

        $contents = $codec->dump(
            '{"name":"example/package","support":{"docs":"https://example.com/docs"},"autoload":{"psr-4":{"App\\\\":"src/"}}}',
            new FundingProfile(['fast-forward']),
        );

        self::assertStringContainsString(
            "\"support\": {\n        \"docs\": \"https://example.com/docs\"\n    },\n    \"funding\": [",
            $contents,
        );
    }
}
