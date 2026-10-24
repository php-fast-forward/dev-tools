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

use FastForward\DevTools\Path\WorkingProjectPathResolver;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\json_decode;

#[CoversNothing]
final class PhpCsFixerConfigTest extends TestCase
{
    /**
     * @param string $runtimePath
     *
     * @return void
     */
    #[Test]
    #[DataProvider('runtimeLayouts')]
    public function configWillLoadWithoutDevToolsAutoloadAndPreserveExclusions(string $runtimePath): void
    {
        $filesystem = new Filesystem();
        $workspace = sys_get_temp_dir() . '/fixer-config-' . bin2hex(random_bytes(8));
        $packageRoot = \dirname(__DIR__, 2);
        $runtime = $workspace . '/' . $runtimePath;
        $consumer = $workspace . '/consumer';

        try {
            $filesystem->mkdir($runtime . '/src/Path');
            $filesystem->copy($packageRoot . '/.php-cs-fixer.dist.php', $runtime . '/.php-cs-fixer.dist.php');
            $filesystem->copy(
                $packageRoot . '/src/Path/WorkingProjectPathResolver.php',
                $runtime . '/src/Path/WorkingProjectPathResolver.php',
            );
            $filesystem->dumpFile($consumer . '/src/Example.php', '<?php echo "consumer";');

            foreach (WorkingProjectPathResolver::TOOLING_EXCLUDED_DIRECTORIES as $directory) {
                $directory = str_replace(['**', '*'], 'nested', $directory);
                $filesystem->dumpFile($consumer . '/' . $directory . '/Excluded.php', '<?php invalid syntax');
            }

            $filesystem->dumpFile($consumer . '/probe.php', <<<'PHP'
                <?php
                require $argv[1] . '/vendor/composer/ClassLoader.php';
                $loader = new Composer\Autoload\ClassLoader();
                foreach (require $argv[1] . '/vendor/composer/autoload_psr4.php' as $namespace => $paths) {
                    if (!str_starts_with($namespace, 'FastForward\\')) {
                        $loader->addPsr4($namespace, $paths);
                    }
                }
                $loader->register();
                if (class_exists(FastForward\DevTools\Path\WorkingProjectPathResolver::class)) {
                    throw new RuntimeException('The consumer must not autoload DevTools.');
                }
                $config = require $argv[2];
                $files = [];
                foreach ($config->getFinder() as $file) {
                    $files[] = $file->getRelativePathname();
                }
                sort($files);
                echo json_encode($files, JSON_THROW_ON_ERROR);
                PHP);
            $process = new Process([
                \PHP_BINARY,
                $consumer . '/probe.php',
                $packageRoot,
                $runtime . '/.php-cs-fixer.dist.php',
            ], $consumer);
            $process->run();

            self::assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
            self::assertSame(['probe.php', 'src/Example.php'], json_decode($process->getOutput(), true));
        } finally {
            $filesystem->remove($workspace);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function runtimeLayouts(): iterable
    {
        yield 'source checkout' => ['runtime'];
        yield 'CPX vendor installation' => ['cpx/vendor/fast-forward/dev-tools'];
    }
}
