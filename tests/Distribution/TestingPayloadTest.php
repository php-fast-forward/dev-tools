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

namespace FastForward\DevTools\Tests\Distribution;

use Composer\Autoload\ClassLoader;
use PharData;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\realpath;

/**
 * Packaged testing seams MUST execute outside the checkout using public dependencies.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversNothing]
final class TestingPayloadTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function archiveWillExecuteTestingSeamsWithoutOriginalDevToolsSourceLoader(): void
    {
        $filesystem = new Filesystem();
        $workspace = sys_get_temp_dir() . '/testing-payload-' . bin2hex(random_bytes(8));
        $packageRoot = \dirname(__DIR__, 2);
        $payload = $workspace . '/payload';
        $consumer = $workspace . '/consumer';

        try {
            $filesystem->mkdir([$payload, $consumer]);
            $payload = realpath($payload);
            $consumer = realpath($consumer);
            $archive = new Process([
                'composer',
                '--no-plugins',
                '--no-scripts',
                '--no-interaction',
                'archive',
                '--format=tar',
                '--dir=' . $workspace,
                '--file=testing-payload',
            ], $packageRoot, [
                'COMPOSER_DISABLE_NETWORK' => '1',
                'GIT_OPTIONAL_LOCKS' => '0',
            ]);
            $archive->setTimeout(60);
            $archive->run();
            self::assertSame(0, $archive->getExitCode(), $archive->getOutput() . $archive->getErrorOutput());
            $archivePath = $workspace . '/testing-payload.tar';
            self::assertFileExists($archivePath);
            (new PharData($archivePath))->extractTo($payload);

            foreach ([
                'src/PhpUnit/Result/ProcessResultInterpreter.php',
                'src/PhpUnit/Bootstrap/BootstrapShimGenerator.php',
                'src/PhpUnit/Coverage/MinimumCoverageResolver.php',
                'phpunit.xml',
                'resources/phpunit.avif',
            ] as $path) {
                self::assertFileExists($payload . '/' . $path);
            }

            foreach (['docs', 'tests'] as $directory) {
                self::assertDirectoryDoesNotExist($payload . '/' . $directory);
            }

            $manifest = '{"name":"fixture/consumer","require":{"php":"^8.3"}}';
            $bootstrap = '<?php $GLOBALS["bootstrap_order"][] = "consumer";';
            $runtime = '<?php $GLOBALS["bootstrap_order"][] = "runtime";';
            $runtimePath = $consumer . "/runtime's files/autoload.php";
            $filesystem->dumpFile($consumer . '/composer.json', $manifest);
            $filesystem->dumpFile($consumer . '/bootstrap.php', $bootstrap);
            $filesystem->dumpFile($runtimePath, $runtime);
            $filesystem->dumpFile($workspace . '/probe.php', <<<'PHP'
                <?php
                $vendor = $argv[1];
                $payload = $argv[2];
                $consumer = $argv[3];
                $runtime = $argv[4];
                require $vendor . '/composer/ClassLoader.php';
                $dependencies = new Composer\Autoload\ClassLoader();
                foreach (require $vendor . '/composer/autoload_psr4.php' as $namespace => $paths) {
                    if (!str_starts_with($namespace, 'FastForward\\')) {
                        $dependencies->addPsr4($namespace, $paths);
                    }
                }
                $classMap = [];
                foreach (require $vendor . '/composer/autoload_classmap.php' as $class => $file) {
                    if (!str_starts_with($class, 'FastForward\\')) {
                        $classMap[$class] = $file;
                    }
                }
                $dependencies->addClassMap($classMap);
                $dependencies->register();
                foreach (require $vendor . '/composer/autoload_files.php' as $file) {
                    if (str_contains(str_replace('\\', '/', $file), '/thecodingmachine/safe/')) {
                        require_once $file;
                    }
                }
                if (class_exists(FastForward\DevTools\PhpUnit\Result\ProcessResultInterpreter::class)
                    || class_exists(FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator::class)) {
                    throw new RuntimeException('Public dependency loader unexpectedly provides DevTools source.');
                }
                spl_autoload_register(static function (string $class) use ($payload): void {
                    $prefix = 'FastForward\\DevTools\\';
                    if (str_starts_with($class, $prefix)) {
                        $path = $payload . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                        if (!is_file($path)) {
                            throw new RuntimeException('Packaged source is missing: ' . $class);
                        }
                        require $path;
                    }
                }, true, true);
                $interpreter = new FastForward\DevTools\PhpUnit\Result\ProcessResultInterpreter();
                $generator = new FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator(
                    new FastForward\DevTools\Filesystem\Filesystem(),
                );
                $minimum = new FastForward\DevTools\PhpUnit\Coverage\MinimumCoverageResolver();
                $shim = $generator->generate($consumer . '/bootstrap.php', $consumer . '/cache', $runtime);
                require $shim;
                $extensionCompatible = class_exists(FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension::class)
                    && is_a(
                        FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension::class,
                        PHPUnit\Runner\Extension\Extension::class,
                        true,
                    );
                $sources = [];
                foreach (get_declared_classes() as $class) {
                    if (str_starts_with($class, 'FastForward\\DevTools\\')) {
                        $source = (new ReflectionClass($class))->getFileName();
                        if (!is_string($source) || !str_starts_with($source, $payload . '/src/')) {
                            throw new RuntimeException('DevTools class loaded outside the packaged payload: ' . $class);
                        }
                        $sources[] = $class;
                    }
                }
                echo json_encode([
                    'result' => $interpreter("Coverage prelude\n{\"result\":\"success\",\"summary\":{\"tests\":1}}", 2),
                    'minimum' => $minimum(' 8e1 '),
                    'extension_compatible' => $extensionCompatible,
                    'order' => $GLOBALS['bootstrap_order'],
                    'sources' => $sources,
                ], JSON_THROW_ON_ERROR);
                PHP);
            $classLoaderFile = (new ReflectionClass(ClassLoader::class))->getFileName();
            self::assertIsString($classLoaderFile);
            $process = new Process([
                \PHP_BINARY,
                $workspace . '/probe.php',
                \dirname($classLoaderFile, 2),
                $payload,
                $consumer,
                $runtimePath,
            ], $consumer);
            $process->setTimeout(10);
            $process->run();

            self::assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
            $output = json_decode($process->getOutput(), true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame([
                'result' => 'failure',
                'summary' => [
                    'tests' => 1,
                ],
                'raw_output' => 'Coverage prelude',
            ], $output['result']);
            self::assertSame(80, $output['minimum']);
            self::assertTrue($output['extension_compatible']);
            self::assertSame(['consumer', 'runtime'], $output['order']);
            self::assertContains(
                'FastForward\\DevTools\\PhpUnit\\Result\\ProcessResultInterpreter',
                $output['sources']
            );
            self::assertContains(
                'FastForward\\DevTools\\PhpUnit\\Bootstrap\\BootstrapShimGenerator',
                $output['sources']
            );
            self::assertContains(
                'FastForward\\DevTools\\PhpUnit\\Coverage\\MinimumCoverageResolver',
                $output['sources']
            );
            self::assertSame($manifest, file_get_contents($consumer . '/composer.json'));
            self::assertSame($bootstrap, file_get_contents($consumer . '/bootstrap.php'));
            self::assertSame($runtime, file_get_contents($runtimePath));
        } finally {
            $filesystem->remove($workspace);
        }
    }
}
