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
use FastForward\DevTools\Funding\FundingYamlCodec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(FundingYamlCodec::class)]
#[UsesClass(FundingProfile::class)]
final class FundingYamlCodecTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function parseWillNormalizeSupportedAndUnsupportedYamlEntries(): void
    {
        $codec = new FundingYamlCodec();

        $profile = $codec->parse(<<<'YAML'
            github:
              - foo
            custom: https://example.com/support
            patreon: example
            YAML);

        self::assertSame(['foo'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
        self::assertSame([
            'patreon' => 'example',
        ], $profile->getUnsupportedYamlEntries());
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillRenderScalarAndListFundingKeys(): void
    {
        $codec = new FundingYamlCodec();

        $contents = $codec->dump(new FundingProfile(
            ['foo'],
            ['https://example.com/support', 'https://example.com/other'],
            [
                'patreon' => 'example',
            ],
        ));

        self::assertSame(
            [
                'patreon' => 'example',
                'github' => 'foo',
                'custom' => ['https://example.com/support', 'https://example.com/other'],
            ],
            Yaml::parse($contents),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillRenderSingleCustomUrlAsList(): void
    {
        $codec = new FundingYamlCodec();

        $contents = $codec->dump(new FundingProfile(['foo'], ['https://example.com/support']));

        self::assertSame(
            [
                'github' => 'foo',
                'custom' => ['https://example.com/support'],
            ],
            Yaml::parse($contents),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function parseWillReturnEmptyProfileForMissingOrInvalidYamlPayloads(): void
    {
        $codec = new FundingYamlCodec();

        self::assertSame([], $codec->parse(null)->getGithubSponsors());
        self::assertSame([], $codec->parse(" \n")->getCustomUrls());
        self::assertSame([], $codec->parse('true')->getGithubSponsors());
        self::assertSame(['just-a-list'], $codec->parse('- just-a-list')->getUnsupportedYamlEntries());
    }

    /**
     * @return void
     */
    #[Test]
    public function parseWillNormalizeListsAndDiscardBlankEntries(): void
    {
        $codec = new FundingYamlCodec();

        $profile = $codec->parse(<<<'YAML'
            github:
              - foo
              - " "
              - bar
            custom:
              - https://example.com/support
              - ""
            YAML);

        self::assertSame(['foo', 'bar'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
    }

    /**
     * @return void
     */
    #[Test]
    public function parseWillNormalizeScalarGithubAndCustomValues(): void
    {
        $codec = new FundingYamlCodec();

        $profile = $codec->parse(<<<'YAML'
            github: foo
            custom: https://example.com/support
            YAML);

        self::assertSame(['foo'], $profile->getGithubSponsors());
        self::assertSame(['https://example.com/support'], $profile->getCustomUrls());
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpWillCollapseSingleGithubSponsorToScalar(): void
    {
        $codec = new FundingYamlCodec();

        $contents = $codec->dump(new FundingProfile(['foo']));

        self::assertSame([
                'github' => 'foo',
            ], Yaml::parse($contents),);
    }
}
