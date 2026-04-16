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

use Exception;
use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Composer\Json\Schema\AuthorInterface;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\ResolverInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

#[CoversClass(Generator::class)]
final class GeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResolverInterface>
     */
    private ObjectProphecy $resolver;

    /**
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composer;

    /**
     * @var ObjectProphecy<ClockInterface>
     */
    private ObjectProphecy $clock;

    /**
     * @var ObjectProphecy<Environment>
     */
    private ObjectProphecy $renderer;

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

        $this->resolver = $this->prophesize(ResolverInterface::class);
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->clock = $this->prophesize(ClockInterface::class);
        $this->renderer = $this->prophesize(Environment::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->generator = new Generator(
            $this->resolver->reveal(),
            $this->composer->reveal(),
            $this->clock->reveal(),
            $this->renderer->reveal(),
            $this->filesystem->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWithUnsupportedLicenseWillReturnNull(): void
    {
        $this->composer->getLicense()
            ->willReturn('GPL-3.0-only');
        $this->resolver->resolve('GPL-3.0-only')
            ->willReturn(null);

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

        $this->composer->getLicense()
            ->willReturn('MIT');

        $this->resolver->resolve('MIT')
            ->willReturn('mit.txt');

        $author = $this->prophesize(AuthorInterface::class);
        $author->__toString()
            ->willReturn('Test Author');

        $this->composer->getAuthors(true)
            ->willReturn($author->reveal());

        $now = new DateTimeImmutable('2026-04-16');
        $this->clock->now()
            ->willReturn($now);

        $renderedContent = 'MIT License\n\nCopyright (c) 2026 Test Author';
        $this->renderer->render('licenses/mit.txt', [
            'copyright_holder' => 'Test Author',
            'year' => '2026',
        ])->willReturn($renderedContent);

        $this->filesystem->dumpFile($targetPath, $renderedContent)
            ->shouldBeCalled();

        $result = $this->generator->generate($targetPath);

        self::assertSame($renderedContent, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillReturnNullOnTemplateError(): void
    {
        $this->composer->getPackageLicense()
            ->willReturn('MIT');
        $this->resolver->resolve('MIT')
            ->willReturn('mit.txt');
        $this->composer->getAuthors(true)
            ->willReturn([]);
        $this->clock->now()
            ->willReturn(new DateTimeImmutable());

        $this->renderer->render(Argument::cetera())->willThrow(new Exception('Twig error'));

        $result = $this->generator->generate('/tmp/LICENSE');

        self::assertNull($result);
    }
}
