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

namespace FastForward\DevTools\Tests\Distribution;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\realpath;
use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\json_encode;

/**
 * An external runtime MUST execute consumer tests without entering its dependency graph.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversNothing]
final class ExternalExecutionTest extends TestCase
{
    private Filesystem $filesystem;

    private string $consumer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->consumer = sys_get_temp_dir() . '/dev-tools-external-' . bin2hex(random_bytes(8));
        $this->filesystem->dumpFile($this->consumer . '/composer.json', json_encode([
            'name' => 'fixture/consumer',
            'require' => [
                'php' => '^8.3',
            ],
            'autoload' => [
                'psr-4' => [
                    'Consumer\\' => 'src/',
                ],
            ],
        ], \JSON_THROW_ON_ERROR));
        $this->filesystem->dumpFile($this->consumer . '/composer.lock', '{"packages":[],"packages-dev":[]}');
        $this->filesystem->dumpFile($this->consumer . '/src/Example.php', <<<'PHP'
            <?php
            namespace Consumer;
            final class Example
            {
                public static function value(): string { return 'consumer source'; }
            }
            PHP);
        $this->filesystem->dumpFile($this->consumer . '/vendor/autoload.php', <<<'PHP'
            <?php
            spl_autoload_register(static function (string $class): void {
                if ('Consumer\\Example' === $class) {
                    require __DIR__ . '/../src/Example.php';
                }
            }, true, true);
            PHP);
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
    public function runtimeDiscoveryWillSeparatePackageAndConsumerPathsWithoutSync(): void
    {
        $process = $this->runCli(['runtime', '--json']);

        self::assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $runtime = json_decode($process->getOutput(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame(realpath(\dirname(__DIR__, 2)), realpath($runtime['package_root']));
        self::assertSame(realpath($this->consumer), realpath($runtime['project_root']));
        self::assertFileExists($runtime['autoload']);
        self::assertDirectoryDoesNotExist($this->consumer . '/.agents');
        self::assertDirectoryDoesNotExist($this->consumer . '/.github');
        self::assertDirectoryDoesNotExist($this->consumer . '/vendor/fast-forward');
    }

    /**
     * @param bool $fails
     *
     * @return void
     */
    #[Test]
    #[DataProvider('consumerResults')]
    public function testsWillUseConsumerAutoloadAndPropagateFailuresWithoutInstallingDevTools(bool $fails): void
    {
        $expected = $fails ? 'incorrect value' : 'consumer source';
        $this->filesystem->dumpFile($this->consumer . '/tests/ConsumerTest.php', str_replace(
            '__EXPECTED__',
            $expected,
            <<<'PHP_WRAP'
                <?php
                use Consumer\Example;
                use PHPUnit\Framework\Attributes\CoversNothing;
                use PHPUnit\Framework\Attributes\Test;
                use PHPUnit\Framework\TestCase;
                #[CoversNothing]
                final class ConsumerTest extends TestCase
                {
                    #[Test]
                    public function sourceIsAvailable(): void
                    {
                        self::assertSame('__EXPECTED__', Example::value());
                    }
                }
                PHP_WRAP
            ,
        ));
        $composerBefore = file_get_contents($this->consumer . '/composer.json');
        $lockBefore = file_get_contents($this->consumer . '/composer.lock');

        $process = $this->runCli(['tests', '--json']);

        self::assertSame($fails ? 1 : 0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $output = $process->getOutput() . $process->getErrorOutput();
        $result = json_decode($output, true, flags: \JSON_THROW_ON_ERROR)['context']['output'];
        self::assertSame($fails ? 'failure' : 'success', $result['result']);
        self::assertSame(1, $result['summary']['tests']);
        self::assertSame(1, $result['summary']['assertions']);
        self::assertSame($fails ? 1 : 0, $result['summary']['failures']);

        if ($fails) {
            self::assertStringContainsString('incorrect value', $output);
        }

        self::assertFileExists($this->consumer . '/.dev-tools/cache/phpunit/bootstrap.php');
        self::assertSame($composerBefore, file_get_contents($this->consumer . '/composer.json'));
        self::assertSame($lockBefore, file_get_contents($this->consumer . '/composer.lock'));
        self::assertDirectoryDoesNotExist($this->consumer . '/vendor/fast-forward');
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function consumerResults(): iterable
    {
        yield 'passing consumer' => [false];
        yield 'failing consumer' => [true];
    }

    /**
     * @param list<string> $arguments
     */
    private function runCli(array $arguments): Process
    {
        $process = new Process([
            \PHP_BINARY,
            \dirname(__DIR__, 2) . '/bin/dev-tools',
            ...$arguments,
            '--no-ansi',
            '--no-interaction',
        ], $this->consumer, [
            'CI' => 'true',
            'COMPOSER_TESTS_ARE_RUNNING' => 'true',
            'FAST_FORWARD_MIN_COVERAGE' => false,
        ]);
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
