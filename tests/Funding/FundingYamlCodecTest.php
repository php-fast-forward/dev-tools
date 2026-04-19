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
        self::assertSame(['patreon' => 'example'], $profile->getUnsupportedYamlEntries());
    }

    #[Test]
    public function dumpWillRenderScalarAndListFundingKeys(): void
    {
        $codec = new FundingYamlCodec();

        $contents = $codec->dump(new FundingProfile(
            ['foo'],
            ['https://example.com/support', 'https://example.com/other'],
            ['patreon' => 'example'],
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

    #[Test]
    public function dumpWillRenderSingleCustomUrlAsList(): void
    {
        $codec = new FundingYamlCodec();

        $contents = $codec->dump(new FundingProfile(
            ['foo'],
            ['https://example.com/support'],
        ));

        self::assertSame(
            [
                'github' => 'foo',
                'custom' => ['https://example.com/support'],
            ],
            Yaml::parse($contents),
        );
    }
}
