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

use FastForward\DevTools\License\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use SplFileObject;

use function Safe\json_encode;

#[CoversClass(Reader::class)]
final class ReaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array $data
     *
     * @return void
     */
    private function createReader(array $data): Reader
    {
        $json = json_encode($data, \JSON_PRETTY_PRINT);

        /** @var ObjectProphecy<SplFileObject> $file */
        $file = $this->prophesize(SplFileObject::class);
        $file->getSize()
            ->willReturn(\strlen($json));
        $file->fread(\strlen($json))
            ->willReturn($json);

        return new Reader($file->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithSingleLicenseWillReturnLicenseString(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
            'license' => ['MIT'],
        ]);

        self::assertSame('MIT', $reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithStringLicenseWillReturnLicenseString(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
            'license' => 'MIT',
        ]);

        self::assertSame('MIT', $reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithNoLicenseWillReturnNull(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertNull($reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWithMultipleLicensesWillReturnNull(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
            'license' => ['MIT', 'Apache-2.0'],
        ]);

        self::assertNull($reader->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getPackageNameWillReturnPackageName(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertSame('fast-forward/dev-tools', $reader->getPackageName());
    }

    /**
     * @return void
     */
    #[Test]
    public function getVendorWillExtractVendorFromPackageName(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertSame('fast-forward', $reader->getVendor());
    }

    /**
     * @return void
     */
    #[Test]
    public function getVendorWithSingleNamePackageWillReturnNull(): void
    {
        $reader = $this->createReader([
            'name' => 'dev-tools',
        ]);

        self::assertNull($reader->getVendor());
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

        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
            'authors' => $authors,
        ]);

        self::assertSame($authors, $reader->getAuthors());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAuthorsWithNoAuthorsWillReturnEmptyArray(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertSame([], $reader->getAuthors());
    }

    /**
     * @return void
     */
    #[Test]
    public function getYearWillReturnCurrentYear(): void
    {
        $reader = $this->createReader([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertSame((int) date('Y'), $reader->getYear());
    }
}
