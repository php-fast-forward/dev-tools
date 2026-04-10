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

use FastForward\DevTools\License\Resolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Resolver::class)]
final class ResolverTest extends TestCase
{
    private Resolver $resolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new Resolver();
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithMITWillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('MIT'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithBSD3ClauseWillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('BSD-3-Clause'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithApache20WillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('Apache-2.0'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithApache2WillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('Apache-2'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithGPL3WillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('GPL-3.0'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithGPL3PlusWillReturnTrue(): void
    {
        self::assertTrue($this->resolver->isSupported('GPL-3+'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isSupportedWithUnknownLicenseWillReturnFalse(): void
    {
        self::assertFalse($this->resolver->isSupported('Unknown-License'));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithMITWillReturnTemplateFilename(): void
    {
        self::assertSame('mit.txt', $this->resolver->resolve('MIT'));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithApache2WillReturnApache20Template(): void
    {
        self::assertSame('apache-2.0.txt', $this->resolver->resolve('Apache-2'));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithUnknownLicenseWillReturnNull(): void
    {
        self::assertNull($this->resolver->resolve('Unknown-License'));
    }
}
