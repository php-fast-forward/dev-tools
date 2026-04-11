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

use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\PlaceholderResolverInterface;
use FastForward\DevTools\License\ReaderInterface;
use FastForward\DevTools\License\ResolverInterface;
use FastForward\DevTools\License\TemplateLoaderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Generator::class)]
final class GeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ReaderInterface>
     */
    private ObjectProphecy $reader;

    /**
     * @var ObjectProphecy<ResolverInterface>
     */
    private ObjectProphecy $resolver;

    /**
     * @var ObjectProphecy<TemplateLoaderInterface>
     */
    private ObjectProphecy $templateLoader;

    /**
     * @var ObjectProphecy<PlaceholderResolverInterface>
     */
    private ObjectProphecy $placeholderResolver;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    private ObjectProphecy $filesystem;

    private Generator $generator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->resolver = $this->prophesize(ResolverInterface::class);
        $this->templateLoader = $this->prophesize(TemplateLoaderInterface::class);
        $this->placeholderResolver = $this->prophesize(PlaceholderResolverInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->generator = new Generator(
            $this->reader->reveal(),
            $this->resolver->reveal(),
            $this->templateLoader->reveal(),
            $this->placeholderResolver->reveal(),
            $this->filesystem->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWithMissingLicenseWillReturnNull(): void
    {
        $this->reader->getLicense()
            ->willReturn(null);

        $result = $this->generator->generate('/tmp/LICENSE');

        self::assertNull($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWithUnsupportedLicenseWillReturnNull(): void
    {
        $this->reader->getLicense()
            ->willReturn('GPL-3.0-only');
        $this->resolver->isSupported('GPL-3.0-only')
            ->willReturn(false);

        $result = $this->generator->generate('/tmp/LICENSE');

        self::assertNull($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillSkipWhenLicenseFileExists(): void
    {
        $this->reader->getLicense()
            ->willReturn('MIT');
        $this->resolver->isSupported('MIT')
            ->willReturn(true);
        $this->filesystem->exists('/tmp/LICENSE')
            ->willReturn(true);

        $result = $this->generator->generate('/tmp/LICENSE');

        self::assertNull($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWithValidLicenseWillCreateFile(): void
    {
        $targetPath = '/tmp/LICENSE';

        $this->reader->getLicense()
            ->willReturn('MIT');
        $this->reader->getAuthors()
            ->willReturn([
                [
                    'name' => 'Test Author',
                    'email' => 'test@example.com',
                ],
            ]);
        $this->reader->getYear()
            ->willReturn(2026);
        $this->reader->getVendor()
            ->willReturn('fast-forward');
        $this->reader->getPackageName()
            ->willReturn('fast-forward/dev-tools');
        $this->resolver->isSupported('MIT')
            ->willReturn(true);
        $this->resolver->resolve('MIT')
            ->willReturn('MIT.txt');
        $this->templateLoader->load('MIT.txt')
            ->willReturn('Copyright {{year}} {{author}}');
        $this->placeholderResolver->resolve(Argument::type('string'), Argument::type('array'))->willReturn(
            'Copyright 2026 Test Author'
        );
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->filesystem->dumpFile($targetPath, Argument::type('string'))->shouldBeCalled();

        $result = $this->generator->generate($targetPath);

        self::assertNotNull($result);
        self::assertStringContainsString('Copyright 2026 Test Author', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillReplacePlaceholders(): void
    {
        $targetPath = uniqid('LICENSE_');

        $this->reader->getLicense()
            ->willReturn('MIT');
        $this->reader->getAuthors()
            ->willReturn([
                [
                    'name' => 'Test Author',
                    'email' => 'test@example.com',
                ],
            ]);
        $this->reader->getYear()
            ->willReturn(2026);
        $this->reader->getVendor()
            ->willReturn('fast-forward');
        $this->reader->getPackageName()
            ->willReturn('fast-forward/dev-tools');
        $this->resolver->isSupported('MIT')
            ->willReturn(true);
        $this->resolver->resolve('MIT')
            ->willReturn('MIT.txt');
        $this->templateLoader->load('MIT.txt')
            ->willReturn('Copyright {{year}} {{author}}');
        $this->placeholderResolver->resolve(Argument::type('string'), Argument::type('array'))->willReturn(
            'Copyright 2026 Test Author fast-forward'
        );
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->filesystem->dumpFile($targetPath, Argument::type('string'))->shouldBeCalled();

        $result = $this->generator->generate($targetPath);

        self::assertNotNull($result);
        self::assertStringContainsString('Test Author', $result);
        self::assertStringContainsString('fast-forward', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function hasLicenseWithValidLicenseWillReturnTrue(): void
    {
        $this->reader->getLicense()
            ->willReturn('MIT');
        $this->resolver->isSupported('MIT')
            ->willReturn(true);

        self::assertTrue($this->generator->hasLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function hasLicenseWithNoLicenseWillReturnFalse(): void
    {
        $this->reader->getLicense()
            ->willReturn(null);

        self::assertFalse($this->generator->hasLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function hasLicenseWithUnsupportedLicenseWillReturnFalse(): void
    {
        $this->reader->getLicense()
            ->willReturn('GPL-3.0-only');
        $this->resolver->isSupported('GPL-3.0-only')
            ->willReturn(false);

        self::assertFalse($this->generator->hasLicense());
    }
}
