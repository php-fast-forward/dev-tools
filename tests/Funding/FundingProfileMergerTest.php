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

use FastForward\DevTools\Funding\FundingProfile;
use FastForward\DevTools\Funding\FundingProfileMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FundingProfileMerger::class)]
#[UsesClass(FundingProfile::class)]
final class FundingProfileMergerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function mergeWillCombineSupportedFundingAndPreserveUnsupportedEntries(): void
    {
        $merger = new FundingProfileMerger();

        $merged = $merger->merge(
            new FundingProfile(
                ['foo'],
                ['https://example.com/a'],
                unsupportedComposerEntries: [[
                    'type' => 'patreon',
                    'url' => 'https://patreon.com/example',
                ]],
            ),
            new FundingProfile(
                ['bar', 'foo'],
                ['https://example.com/b'],
                [
                    'ko_fi' => 'example',
                ],
            ),
        );

        self::assertSame(['bar', 'foo'], $merged->getGithubSponsors());
        self::assertSame(['https://example.com/a', 'https://example.com/b'], $merged->getCustomUrls());
        self::assertSame([
            'ko_fi' => 'example',
        ], $merged->getUnsupportedYamlEntries());
        self::assertSame(
            [[
                'type' => 'patreon',
                'url' => 'https://patreon.com/example',
            ]],
            $merged->getUnsupportedComposerEntries(),
        );
    }
}
