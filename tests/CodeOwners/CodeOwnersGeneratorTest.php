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

namespace FastForward\DevTools\Tests\CodeOwners;

use FastForward\DevTools\CodeOwners\CodeOwnersGenerator;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Composer\Json\Schema\Author;
use FastForward\DevTools\Composer\Json\Schema\Support;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;

#[CoversClass(CodeOwnersGenerator::class)]
#[UsesClass(Author::class)]
#[UsesClass(Support::class)]
final class CodeOwnersGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composerJson;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    private CodeOwnersGenerator $generator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composerJson = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);

        $this->fileLocator->locate('resources/CODEOWNERS.dist')
            ->willReturn('/package/resources/CODEOWNERS.dist');
        $this->filesystem->readFile('/package/resources/CODEOWNERS.dist')
            ->willReturn(<<<'TEXT'
                # Header
                {{ suggestions }}
                {{ rule }}
                TEXT);

        $this->generator = new CodeOwnersGenerator(
            $this->composerJson->reveal(),
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function inferOwnersWillCollectGroupAndAuthorOwners(): void
    {
        $this->composerJson->getSupport()
            ->willReturn(new Support(source: 'https://github.com/php-fast-forward/dev-tools'));
        $this->composerJson->getAuthors()
            ->willReturn([
                new Author(homepage: 'https://github.com/php-fast-forward'),
                new Author(homepage: 'https://github.com/mentordosnerds/'),
                new Author(homepage: 'https://example.com/not-github'),
            ]);

        self::assertSame(['@php-fast-forward', '@mentordosnerds'], $this->generator->inferOwners());
    }

    /**
     * @return void
     */
    #[Test]
    public function inferGroupOwnerWillReturnRepositoryOwnerFromSupportSource(): void
    {
        $this->composerJson->getSupport()
            ->willReturn(new Support(source: 'https://github.com/php-fast-forward/dev-tools'));

        self::assertSame('@php-fast-forward', $this->generator->inferGroupOwner());
    }

    /**
     * @return void
     */
    #[Test]
    public function inferOwnersWillIgnoreUnsupportedAuthorEntriesAndInvalidUrls(): void
    {
        $this->composerJson->getSupport()
            ->willReturn(new Support(source: 'https://github.com/php-fast-forward/dev-tools'));
        $this->composerJson->getAuthors()
            ->willReturn([
                'not-an-author',
                new Author(homepage: 'https://github.com/mentordosnerds/dev-tools'),
                new Author(homepage: 'https://github.com'),
            ]);

        self::assertSame(['@php-fast-forward'], $this->generator->inferOwners());
    }

    /**
     * @return void
     */
    #[Test]
    public function inferGroupOwnerWillReturnNullWhenSupportSourceIsMissingOrInvalid(): void
    {
        $this->composerJson->getSupport()
            ->willReturn(new Support(source: 'https://example.com/php-fast-forward/dev-tools'));

        self::assertNull($this->generator->inferGroupOwner());
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillRenderExplicitOwners(): void
    {
        self::assertSame(
            "# Header\n\n* @php-fast-forward @mentordosnerds",
            $this->generator->generate(['@php-fast-forward', '@mentordosnerds']),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillRenderCommentedFallbackWhenOwnersCannotBeInferred(): void
    {
        $this->composerJson->getAuthors()
            ->willReturn([]);
        $this->composerJson->getSupport()
            ->willReturn(new Support());

        self::assertSame(
            "# Header\n# No GitHub owners could be inferred from composer.json metadata.\n# * @your-github-user",
            $this->generator->generate(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillRenderGroupOwnerWhenAuthorCannotBeInferred(): void
    {
        $this->composerJson->getAuthors()
            ->willReturn([]);
        $this->composerJson->getSupport()
            ->willReturn(new Support(source: 'https://github.com/php-fast-forward/dev-tools'));

        self::assertSame("# Header\n\n* @php-fast-forward", $this->generator->generate());
    }

    /**
     * @return void
     */
    #[Test]
    public function normalizeOwnersWillPreserveHandlesAndEmails(): void
    {
        self::assertSame(
            ['@php-fast-forward', '@mentordosnerds', 'security@example.com'],
            $this->generator->normalizeOwners('php-fast-forward, @mentordosnerds security@example.com'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function normalizeOwnersWillDropEmptyTokensAndDeduplicateRepeatedOwners(): void
    {
        self::assertSame(
            ['@php-fast-forward', 'security@example.com'],
            $this->generator->normalizeOwners('  php-fast-forward,, @php-fast-forward security@example.com  '),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function extractGitHubHelpersWillHandleSupportedAndUnsupportedUrls(): void
    {
        $handleMethod = new ReflectionMethod($this->generator, 'extractGitHubHandleFromUrl');
        $ownerMethod = new ReflectionMethod($this->generator, 'extractGitHubRepositoryOwner');
        $pathMethod = new ReflectionMethod($this->generator, 'githubPath');

        self::assertSame(
            'mentordosnerds',
            $handleMethod->invoke($this->generator, 'https://github.com/mentordosnerds/')
        );
        self::assertNull($handleMethod->invoke($this->generator, 'https://github.com/mentordosnerds/dev-tools'));
        self::assertSame(
            'php-fast-forward',
            $ownerMethod->invoke($this->generator, 'https://github.com/php-fast-forward/dev-tools/')
        );
        self::assertNull($ownerMethod->invoke($this->generator, 'https://github.com/php-fast-forward'));
        self::assertSame(
            '//php-fast-forward///dev-tools/',
            $pathMethod->invoke($this->generator, 'https://github.com//php-fast-forward///dev-tools/')
        );
        self::assertNull($pathMethod->invoke($this->generator, 'https://example.com/php-fast-forward/dev-tools'));
    }
}
