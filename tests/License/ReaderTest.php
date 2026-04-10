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

use PHPUnit\Framework\MockObject\MockObject;
use FastForward\DevTools\License\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(Reader::class)]
final class ReaderTest extends TestCase
{
    use ProphecyTrait;

    private Reader $reader;

    private MockObject $composer;

    private MockObject $package;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = $this->createMock(Composer::class);
        $this->package = $this->createMock(RootPackageInterface::class);

        $this->composer->method('getPackage')
            ->willReturn($this->package);

        $this->reader = new Reader($this->composer);
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithSingleLicenseWillReturnLicenseString(): void
    {
        $this->package->method('getLicense')
            ->willReturn(['MIT']);

        self::assertSame('MIT', $this->reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithNoLicenseWillReturnNull(): void
    {
        $this->package->method('getLicense')
            ->willReturn([]);

        self::assertNull($this->reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithMultipleLicensesWillReturnNull(): void
    {
        $this->package->method('getLicense')
            ->willReturn(['MIT', 'Apache-2.0']);

        self::assertNull($this->reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getPackageNameWillReturnPackageName(): void
    {
        $this->package->method('getName')
            ->willReturn('fast-forward/dev-tools');

        self::assertSame('fast-forward/dev-tools', $this->reader->getPackageName());
    }

    /**
     * @return void
     */
    #[Test]
    public function getVendorWillExtractVendorFromPackageName(): void
    {
        $this->package->method('getName')
            ->willReturn('fast-forward/dev-tools');
        $this->package->method('getLicense')
            ->willReturn(['MIT']);

        self::assertSame('fast-forward', $this->reader->getVendor());
    }

    /**
     * @return void
     */
    #[Test]
    public function getVendorWithSingleNamePackageWillReturnNull(): void
    {
        $this->package->method('getName')
            ->willReturn('dev-tools');
        $this->package->method('getLicense')
            ->willReturn(['MIT']);

        self::assertNull($this->reader->getVendor());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAuthorsWillReturnAuthorsArray(): void
    {
        $authors = [
            [
                'name' => 'Felipe Abreu',
                'email' => 'test@example.com',
                'homepage' => 'https://example.com',
                'role' => 'Developer',
            ],
        ];

        $this->package->method('getAuthors')
            ->willReturn($authors);

        self::assertSame($authors, $this->reader->getAuthors());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAuthorsWithNoAuthorsWillReturnEmptyArray(): void
    {
        $this->package->method('getAuthors')
            ->willReturn([]);

        self::assertSame([], $this->reader->getAuthors());
    }

    /**
     * @return void
     */
    #[Test]
    public function getYearWillReturnCurrentYear(): void
    {
        self::assertSame((int) date('Y'), $this->reader->getYear());
    }
}
