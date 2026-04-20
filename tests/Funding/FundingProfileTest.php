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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FundingProfile::class)]
final class FundingProfileTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function accessorsWillReturnNormalizedProfileValues(): void
    {
        $profile = new FundingProfile(
            ['fast-forward'],
            ['https://example.com/support'],
            ['ko_fi' => 'fastforward'],
            [['type' => 'patreon', 'url' => 'https://patreon.com/example']],
        );

        self::assertSame(['fast-forward'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
        self::assertSame(['ko_fi' => 'fastforward'], $profile->getUnsupportedYamlEntries());
        self::assertSame(
            [['type' => 'patreon', 'url' => 'https://patreon.com/example']],
            $profile->getUnsupportedComposerEntries(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function hasYamlContentWillReportWhetherYamlShouldExist(): void
    {
        self::assertFalse((new FundingProfile())->hasYamlContent());
        self::assertTrue((new FundingProfile(['fast-forward']))->hasYamlContent());
        self::assertTrue((new FundingProfile(customUrls: ['https://example.com/support']))->hasYamlContent());
        self::assertTrue((new FundingProfile(unsupportedYamlEntries: ['ko_fi' => 'fastforward']))->hasYamlContent());
    }
}
