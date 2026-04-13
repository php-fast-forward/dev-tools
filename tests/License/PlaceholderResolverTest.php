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

namespace FastForward\DevTools\Tests\License;

use DateTimeImmutable;
use FastForward\DevTools\License\PlaceholderResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;

#[CoversClass(PlaceholderResolver::class)]
final class PlaceholderResolverTest extends TestCase
{
    use ProphecyTrait;

    private PlaceholderResolver $resolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()
            ->willReturn(new DateTimeImmutable('2026-01-01 00:00:00'));

        $this->resolver = new PlaceholderResolver($clock->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithAllPlaceholdersWillReplaceAll(): void
    {
        $template = 'Copyright {{ year }} {{ organization }} {{ author }} {{ project }}';
        $metadata = [
            'year' => 2026,
            'organization' => 'FastForward',
            'author' => 'Felipe',
            'project' => 'dev-tools',
        ];

        $result = $this->resolver->resolve($template, $metadata);

        self::assertSame('Copyright 2026 FastForward Felipe dev-tools', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithPartialMetadataWillReplaceAvailableAndRemoveOthers(): void
    {
        $template = 'Copyright {{ year }} {{ organization }}{{ author }}';
        $metadata = [
            'year' => 2026,
        ];

        $result = $this->resolver->resolve($template, $metadata);

        self::assertSame('Copyright 2026', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithNoMetadataWillRemovePlaceholders(): void
    {
        $template = 'Copyright {{ year }} {{ organization }}{{ author }} {{ project }}';
        $metadata = [];

        $result = $this->resolver->resolve($template, $metadata);

        self::assertStringNotContainsString('{{', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillCollapseMultipleBlankLines(): void
    {
        $template = "Line 1\n\n\n\nLine 2";
        $metadata = [];

        $result = $this->resolver->resolve($template, $metadata);

        self::assertSame("Line 1\n\nLine 2", $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillTrimResult(): void
    {
        $template = '  {{ year }}  ';
        $metadata = [
            'year' => 2026,
        ];

        $result = $this->resolver->resolve($template, $metadata);

        self::assertSame('2026', $result);
    }
}
