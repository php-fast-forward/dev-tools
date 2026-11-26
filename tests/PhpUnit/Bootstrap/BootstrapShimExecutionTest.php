<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\PhpUnit\Bootstrap;

use Composer\Autoload\ClassLoader;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function Safe\json_decode;

/**
 * Generated shims MUST preserve consumer autoload precedence and bootstrap failures.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(BootstrapShimGenerator::class)]
#[UsesClass(DevToolsPathResolver::class)]
final class BootstrapShimExecutionTest extends TestCase
{
    use ProphecyTrait;

    private Filesystem $filesystem;

    private string $consumer;

    private BootstrapShimGenerator $generator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->consumer = sys_get_temp_dir() . '/testing-bootstrap-' . bin2hex(random_bytes(8));
        $this->filesystem->dumpFile($this->consumer . '/composer.json', '{"name":"fixture/consumer"}');
        $filesystem = $this->prophesize(FilesystemInterface::class);
        $writer = $this->filesystem;
        $filesystem->dumpFile(Argument::type('string'), Argument::type('string'))
            ->will(static function (array $arguments) use ($writer): void {
                $writer->dumpFile(...$arguments);
            })
            ->shouldBeCalledOnce();
        $this->generator = new BootstrapShimGenerator($filesystem->reveal());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->consumer);
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerBootstrapWillTakePrecedenceOverPreloadedRuntimeAutoloader(): void
    {
        $bootstrap = <<<'PHP'
            <?php
            spl_autoload_register(static function (string $class): void {
                if ('Symfony\\Component\\Filesystem\\Filesystem' === $class) {
                    require __DIR__ . '/ConsumerFilesystem.php';
                }
            }, true, true);
            PHP;
        $manifest = file_get_contents($this->consumer . '/composer.json');
        $this->filesystem->dumpFile($this->consumer . '/bootstrap.php', $bootstrap);
        $this->filesystem->dumpFile($this->consumer . '/ConsumerFilesystem.php', <<<'PHP'
            <?php
            namespace Symfony\Component\Filesystem;
            final class Filesystem
            {
                public const ORIGIN = 'consumer';
            }
            PHP);
        $shim = $this->generator->generate($this->consumer . '/bootstrap.php', $this->consumer . '/cache');
        $process = $this->runShim($shim, <<<'PHP'
            require $argv[2];
            require $argv[1];
            echo json_encode([
                'origin' => Symfony\Component\Filesystem\Filesystem::ORIGIN,
                'runtime_available' => class_exists(Symfony\Component\Filesystem\Path::class),
            ], JSON_THROW_ON_ERROR);
            PHP);

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame([
            'origin' => 'consumer',
            'runtime_available' => true,
        ], json_decode($process->getOutput(), true, flags: \JSON_THROW_ON_ERROR));
        self::assertSame($bootstrap, file_get_contents($this->consumer . '/bootstrap.php'));
        self::assertSame($manifest, file_get_contents($this->consumer . '/composer.json'));
    }

    /**
     * @return void
     */
    #[Test]
    public function explicitRuntimeAutoloadWillLoadAfterConsumerWithoutRetainingDevToolsPath(): void
    {
        $bootstrap = '<?php $GLOBALS["bootstrap_order"][] = "consumer";';
        $runtime = '<?php $GLOBALS["bootstrap_order"][] = "runtime";';
        $manifest = file_get_contents($this->consumer . '/composer.json');
        $runtimeAutoload = $this->consumer . "/runtime's files/autoload.php";
        $this->filesystem->dumpFile($this->consumer . '/bootstrap.php', $bootstrap);
        $this->filesystem->dumpFile($runtimeAutoload, $runtime);
        $shim = $this->generator->generate(
            $this->consumer . '/bootstrap.php',
            $this->consumer . '/cache',
            $runtimeAutoload,
        );
        $process = $this->runShim($shim, <<<'PHP'
            require $argv[1];
            echo json_encode($GLOBALS['bootstrap_order'], JSON_THROW_ON_ERROR);
            PHP);

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame(
            ['consumer', 'runtime'],
            json_decode($process->getOutput(), true, flags: \JSON_THROW_ON_ERROR)
        );
        self::assertStringContainsString(var_export($runtimeAutoload, true), file_get_contents($shim));
        self::assertStringNotContainsString(
            var_export(DevToolsPathResolver::getRuntimeAutoloadPath(), true),
            file_get_contents($shim),
        );
        self::assertSame($bootstrap, file_get_contents($this->consumer . '/bootstrap.php'));
        self::assertSame($runtime, file_get_contents($runtimeAutoload));
        self::assertSame($manifest, file_get_contents($this->consumer . '/composer.json'));
    }

    /**
     * @return void
     */
    #[Test]
    public function missingConsumerBootstrapWillFailBeforeContinuingExecution(): void
    {
        $manifest = file_get_contents($this->consumer . '/composer.json');
        $missingBootstrap = $this->consumer . '/missing-bootstrap.php';
        $shim = $this->generator->generate($missingBootstrap, $this->consumer . '/cache');
        $process = $this->runShim($shim, <<<'PHP'
            require $argv[2];
            require $argv[1];
            echo 'bootstrap unexpectedly continued';
            PHP);

        self::assertNotSame(0, $process->getExitCode());
        self::assertStringContainsString($missingBootstrap, $process->getErrorOutput());
        self::assertStringNotContainsString('bootstrap unexpectedly continued', $process->getOutput());
        self::assertFileDoesNotExist($missingBootstrap);
        self::assertSame($manifest, file_get_contents($this->consumer . '/composer.json'));
    }

    /**
     * @param string $shim the generated bootstrap shim path
     * @param string $code the PHP code executed in the fixture process
     *
     * @return Process the completed fixture process
     */
    private function runShim(string $shim, string $code): Process
    {
        $classLoaderFile = (new ReflectionClass(ClassLoader::class))->getFileName();
        self::assertIsString($classLoaderFile);
        $runtimeAutoload = \dirname($classLoaderFile, 2) . '/autoload.php';
        $process = new Process([
            \PHP_BINARY,
            '-d',
            'display_errors=stderr',
            '-r',
            $code,
            $shim,
            $runtimeAutoload,
        ], $this->consumer);
        $process->setTimeout(10);
        $process->run();

        return $process;
    }
}
