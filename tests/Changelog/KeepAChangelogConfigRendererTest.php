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

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\KeepAChangelogConfigRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeepAChangelogConfigRenderer::class)]
final class KeepAChangelogConfigRendererTest extends TestCase
{
    private KeepAChangelogConfigRenderer $renderer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new KeepAChangelogConfigRenderer();
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnKeepAChangelogConfiguration(): void
    {
        $output = $this->renderer->render();

        self::assertStringContainsString('[defaults]', $output);
        self::assertStringContainsString('changelog_file = CHANGELOG.md', $output);
        self::assertStringContainsString('provider = github', $output);
        self::assertStringContainsString('remote = origin', $output);
        self::assertStringContainsString('[providers]', $output);
    }
}
