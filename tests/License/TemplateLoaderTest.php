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

use FastForward\DevTools\License\TemplateLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TemplateLoader::class)]
final class TemplateLoaderTest extends TestCase
{
    private TemplateLoader $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new TemplateLoader();
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWithExistingTemplateWillReturnContent(): void
    {
        $content = $this->loader->load('mit.txt');

        self::assertStringContainsString('MIT', $content);
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWithNonExistentTemplateWillThrow(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found');

        $this->loader->load('nonexistent.txt');
    }
}
