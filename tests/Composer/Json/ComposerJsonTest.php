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

namespace FastForward\DevTools\Tests\Composer\Json;

use FastForward\DevTools\Composer\Json\ComposerJson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\tempnam;
use function Safe\unlink;

#[CoversClass(ComposerJson::class)]
final class ComposerJsonTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function accessorsWillReturnConfiguredComposerData(): void
    {
        $composerJson = $this->createComposerJson([
            'name' => 'fast-forward/dev-tools',
            'description' => 'Fast Forward Development Tools for PHP projects',
            'license' => 'MIT',
            'authors' => [
                [
                    'name' => 'Felipe',
                ],
            ],
            'extra' => [
                'gitattributes' => [
                    'keep-in-export' => ['/.github/'],
                ],
            ],
            'autoload' => [
                'psr-4' => [
                    'FastForward\\DevTools\\' => 'src/',
                ],
            ],
        ]);

        self::assertSame('fast-forward/dev-tools', $composerJson->getPackageName());
        self::assertSame('Fast Forward Development Tools for PHP projects', $composerJson->getPackageDescription());
        self::assertSame('MIT', $composerJson->getPackageLicense());
        self::assertSame([[
            'name' => 'Felipe',
        ]], $composerJson->getAuthors());
        self::assertSame([
            'gitattributes' => [
                'keep-in-export' => ['/.github/'],
            ],
        ], $composerJson->getExtra(),);
        self::assertSame([
            'FastForward\\DevTools\\' => 'src/',
        ], $composerJson->getAutoload());
    }

    /**
     * @return void
     */
    #[Test]
    public function getPackageLicenseWillReturnSingleLicenseFromArray(): void
    {
        $composerJson = $this->createComposerJson([
            'license' => ['MIT'],
        ]);

        self::assertSame('MIT', $composerJson->getPackageLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getPackageLicenseWillReturnNullForMultipleLicenses(): void
    {
        $composerJson = $this->createComposerJson([
            'license' => ['MIT', 'Apache-2.0'],
        ]);

        self::assertNull($composerJson->getPackageLicense());
    }

    /**
     * @param array<string, mixed> $contents
     *
     * @return ComposerJson
     */
    private function createComposerJson(array $contents): ComposerJson
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'composer-json-');
        $this->temporaryFiles[] = $temporaryFile;

        file_put_contents($temporaryFile, json_encode($contents, \JSON_THROW_ON_ERROR));

        return new ComposerJson($temporaryFile);
    }
}
