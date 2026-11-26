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

namespace FastForward\DevTools\Tests\Tooling;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\Tooling\ToolingProfile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function Safe\chdir;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;

/**
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(ToolingProfile::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(Filesystem::class)]
final class ToolingProfileTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ToolingProfile $profile;

    private string $path;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->path = WorkingProjectPathResolver::getProjectPath('dev-tools.json');
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem->exists(Argument::any())->willReturn(false);
        $this->profile = new ToolingProfile($this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function absentProfileWillRetainLegacySelection(): void
    {
        $this->filesystem->exists($this->path)
            ->willReturn(false);
        self::assertNull($this->profile->read());
    }

    /**
     * @return void
     */
    #[Test]
    public function emptyExplicitProfileWillRemainDistinctFromAnAbsentProfile(): void
    {
        $this->existing('{"version":1,"capabilities":[]}');
        self::assertSame([], $this->profile->read());
    }

    /**
     * @param string $json
     *
     * @return void
     */
    #[Test]
    #[DataProvider('invalidProfiles')]
    public function invalidSchemaWillBeRejectedWithoutWriting(string $json): void
    {
        $this->existing($json);
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();
        $this->expectException(InvalidArgumentException::class);
        $this->profile->configure(['tests']);
    }

    /**
     * @return iterable
     */
    public static function invalidProfiles(): iterable
    {
        yield 'broken json' => ['{'];
        yield 'root list' => ['[]'];
        yield 'missing version' => ['{"capabilities":[]}'];
        yield 'string version' => ['{"version":"1","capabilities":[]}'];
        yield 'future version' => ['{"version":2,"capabilities":[]}'];
        yield 'missing capabilities' => ['{"version":1}'];
        yield 'object capabilities' => ['{"version":1,"capabilities":{}}'];
        yield 'unknown key' => ['{"version":1,"capabilities":[],"install":true}'];
        yield 'unknown capability' => ['{"version":1,"capabilities":["unknown"]}'];
        yield 'duplicate capability' => ['{"version":1,"capabilities":["tests","tests"]}'];
        yield 'non-string capability' => ['{"version":1,"capabilities":[false]}'];
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillWriteCanonicalSelectionOnlyWhenChanged(): void
    {
        $this->filesystem->exists($this->path)
            ->willReturn(false);
        $this->filesystem->dumpFile(
            $this->path,
            Argument::that(static fn(string $content): bool => json_decode($content, true) === [
                'version' => 1,
                'capabilities' => ['tests', 'docs'],
            ])
        )->shouldBeCalledOnce();

        self::assertTrue($this->profile->configure(['docs', 'tests']));
    }

    /**
     * @return void
     */
    #[Test]
    public function unchangedSelectionWillPreserveExistingFormatting(): void
    {
        $this->existing('{"version":1,"capabilities":["docs","tests"]}');
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertFalse($this->profile->configure(['tests', 'docs']));
    }

    /**
     * @return void
     */
    #[Test]
    public function dryRunWillReportChangeWithoutWriting(): void
    {
        $this->filesystem->exists($this->path)
            ->willReturn(false);
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertTrue($this->profile->configure(['quality'], true));
    }

    /**
     * @return void
     */
    #[Test]
    public function statusWillReportSelectionSeparatelyFromPreferredBinaryAvailability(): void
    {
        $this->existing('{"version":1,"capabilities":["tests"]}');
        $status = $this->profile->status();

        self::assertSame(ToolingProfile::CAPABILITIES, array_keys($status));
        self::assertTrue($status['tests']['selected']);
        self::assertFalse($status['docs']['selected']);
        self::assertSame(
            DevToolsPathResolver::getPreferredToolBinaryPath('phpunit'),
            $status['tests']['binaries']['phpunit']
        );
        self::assertSame([], $status['tests']['missing']);
        self::assertTrue($status['tests']['available']);
        $this->profile->assertAvailable(['tests']);
    }

    /**
     * @return void
     */
    #[Test]
    public function missingExecutableWillFailEvenWhenAConsumerBinaryFileExists(): void
    {
        $original = getcwd();
        $directory = sys_get_temp_dir() . '/tooling-profile-' . bin2hex(random_bytes(4));
        mkdir($directory . '/vendor/bin', 0o777, true);
        file_put_contents($directory . '/vendor/bin/phpunit', 'not executable');

        try {
            chdir($directory);
            $profile = new ToolingProfile();
            self::assertFalse($profile->status()['tests']['available']);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Selected capability tests is missing tools: phpunit.');
            $profile->assertAvailable(['tests']);
        } finally {
            chdir($original);
            (new Filesystem())->remove($directory);
        }
    }

    /**
     * @param string $json
     *
     * @return void
     */
    private function existing(string $json): void
    {
        $this->filesystem->exists($this->path)
            ->willReturn(true);
        $this->filesystem->readFile($this->path)
            ->willReturn($json);
    }

    /**
     * @return void
     */
    #[Test]
    public function absentComposerWillMakeQualityAndDependenciesUnavailableDespiteInstalledEngines(): void
    {
        $this->existing('{"version":1,"capabilities":["quality","dependencies"]}');
        $this->filesystem->exists(
            DevToolsPathResolver::getRuntimeVendorPath('ergebnis/composer-normalize/composer.json')
        )->willReturn(true);
        $executables = $this->prophesize(ExecutableFinder::class);
        $executables->find('composer')
            ->willReturn(null);
        $profile = new ToolingProfile($this->filesystem->reveal(), $executables->reveal());
        $status = $profile->status();

        foreach (['quality', 'dependencies'] as $capability) {
            self::assertFalse($status[$capability]['available']);
            self::assertSame(['composer'], $status[$capability]['missing']);
            foreach ($status[$capability]['binaries'] as $name => $path) {
                if ('composer' !== $name) {
                    self::assertTrue(is_executable($path), $path);
                }
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function qualityWillRequireNormalizerInTheToolRuntimeInsteadOfTheConsumer(): void
    {
        $this->existing('{"version":1,"capabilities":["quality"]}');
        $manifest = DevToolsPathResolver::getRuntimeVendorPath('ergebnis/composer-normalize/composer.json');
        $this->filesystem->exists($manifest)
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $executables = $this->prophesize(ExecutableFinder::class);
        $executables->find('composer')
            ->willReturn('/tools/composer');

        $status = (new ToolingProfile($this->filesystem->reveal(), $executables->reveal()))->status();

        self::assertFalse($status['quality']['available']);
        self::assertSame(['ergebnis/composer-normalize'], $status['quality']['missing']);
        self::assertSame('/tools/composer', $status['quality']['binaries']['composer']);
    }
}
