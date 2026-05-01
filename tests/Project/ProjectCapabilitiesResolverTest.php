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

namespace FastForward\DevTools\Tests\Project;

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\Filesystem;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Project\ProjectCapabilities;
use FastForward\DevTools\Project\ProjectCapabilitiesResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

use function Safe\chdir;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;

#[CoversClass(ProjectCapabilitiesResolver::class)]
#[UsesClass(Filesystem::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(ProjectCapabilities::class)]
final class ProjectCapabilitiesResolverTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $composer;

    private SymfonyFilesystem $filesystem;

    private ProjectCapabilitiesResolver $resolver;

    private string $workingDirectory;

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = new SymfonyFilesystem();
        $this->workingDirectory = getcwd();
        $this->workspace = sys_get_temp_dir() . '/dev-tools-project-capabilities-' . uniqid('', true);

        $this->filesystem->mkdir($this->workspace);
        chdir($this->workspace);

        $this->resolver = new ProjectCapabilitiesResolver($this->composer->reveal(), new Filesystem());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        chdir($this->workingDirectory);
        $this->filesystem->remove($this->workspace);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillDetectGuideOnlyRepositoryCapabilities(): void
    {
        mkdir($this->workspace . '/docs', recursive: true);
        file_put_contents($this->workspace . '/docs/index.rst', "Guide\n=====\n");

        $this->composer->getAutoload('psr-4')
            ->willReturn([]);
        $this->composer->getAutoload('psr-0')
            ->willReturn([]);
        $this->composer->getAutoload('classmap')
            ->willReturn([]);

        $capabilities = $this->resolver->resolve();

        self::assertTrue($capabilities->hasGuideDirectory());
        self::assertFalse($capabilities->hasTestsPath());
        self::assertFalse($capabilities->hasWikiTarget());
        self::assertFalse($capabilities->hasPhpSourceFiles());
        self::assertFalse($capabilities->canGenerateApiDocumentation());
        self::assertTrue($capabilities->canGenerateDocs());
        self::assertFalse($capabilities->canRunTests());
        self::assertTrue($capabilities->canGenerateMetrics());
        self::assertFalse($capabilities->canGenerateWiki());
        self::assertSame([], $capabilities->getApiDirectories());
        self::assertNull($capabilities->getDefaultPackageName());
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillIgnoreToolingPhpFilesWhenDetectingTestablePhpSource(): void
    {
        file_put_contents($this->workspace . '/ecs.php', "<?php\n\nreturn [];\n");

        $this->composer->getAutoload('psr-4')
            ->willReturn([]);
        $this->composer->getAutoload('psr-0')
            ->willReturn([]);
        $this->composer->getAutoload('classmap')
            ->willReturn([]);

        $capabilities = $this->resolver->resolve();

        self::assertFalse($capabilities->hasPhpSourceFiles());
        self::assertFalse($capabilities->canRunTests());
        self::assertTrue($capabilities->canGenerateMetrics());
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillDetectPhpSourceFromFileBasedClassmapEntries(): void
    {
        mkdir($this->workspace . '/legacy', recursive: true);
        file_put_contents($this->workspace . '/legacy/LegacyClass.php', "<?php\n\nfinal class LegacyClass {}\n");

        $this->composer->getAutoload('psr-4')
            ->willReturn([]);
        $this->composer->getAutoload('psr-0')
            ->willReturn([]);
        $this->composer->getAutoload('classmap')
            ->willReturn(['legacy/LegacyClass.php']);

        $capabilities = $this->resolver->resolve();

        self::assertTrue($capabilities->hasPhpSourceFiles());
        self::assertTrue($capabilities->canRunTests());
        self::assertFalse($capabilities->canGenerateApiDocumentation());
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillDetectPhpPackageCapabilities(): void
    {
        mkdir($this->workspace . '/src', recursive: true);
        mkdir($this->workspace . '/tests', recursive: true);
        mkdir($this->workspace . '/docs', recursive: true);
        mkdir($this->workspace . '/.github/wiki', recursive: true);

        file_put_contents($this->workspace . '/src/Feature.php', "<?php\n\nnamespace App;\n\nfinal class Feature {}\n");
        file_put_contents($this->workspace . '/tests/FeatureTest.php', "<?php\n\nfinal class FeatureTest {}\n");
        file_put_contents($this->workspace . '/docs/index.rst', "Guide\n=====\n");

        $this->composer->getAutoload('psr-4')
            ->willReturn([
                'App\\' => 'src/',
            ]);
        $this->composer->getAutoload('psr-0')
            ->willReturn([]);
        $this->composer->getAutoload('classmap')
            ->willReturn([]);

        $capabilities = $this->resolver->resolve();

        self::assertTrue($capabilities->hasGuideDirectory());
        self::assertTrue($capabilities->hasTestsPath());
        self::assertTrue($capabilities->hasWikiTarget());
        self::assertTrue($capabilities->hasPhpSourceFiles());
        self::assertTrue($capabilities->canGenerateApiDocumentation());
        self::assertTrue($capabilities->canGenerateDocs());
        self::assertTrue($capabilities->canRunTests());
        self::assertTrue($capabilities->canGenerateMetrics());
        self::assertTrue($capabilities->canGenerateWiki());
        self::assertSame(['src/'], $capabilities->getApiDirectories());
        self::assertSame('App', $capabilities->getDefaultPackageName());
    }
}
