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

    /**
     * @return void
     */
    #[Test]
    public function resolveWithMissingLicenseWillReturnNull(): void
    {
        self::assertNull($this->resolver->resolve(null));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWithEmptyLicenseWillReturnNull(): void
    {
        self::assertNull($this->resolver->resolve('  '));
    }
}
