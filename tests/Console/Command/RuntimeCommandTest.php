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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\RuntimeCommand;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\SelfUpdate\VersionCheckerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\getcwd;
use function Safe\json_decode;
use function Safe\mkdir;
use function Safe\realpath;

#[CoversClass(RuntimeCommand::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
#[UsesTrait(HasJsonOption::class)]
final class RuntimeCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function runtimeWillEmitFlatJsonWithPackagePathsAndVersion(): void
    {
        $versionChecker = $this->prophesize(VersionCheckerInterface::class);
        $versionChecker->getCurrentVersion()
            ->willReturn('2.0.0')
            ->shouldBeCalledOnce();
        $tester = new CommandTester(new RuntimeCommand($versionChecker->reveal()));
        $workingDirectory = getcwd();

        self::assertSame(RuntimeCommand::SUCCESS, $tester->execute([
            '--json' => true,
        ]));
        $metadata = json_decode($tester->getDisplay(), true);
        $packageRoot = \dirname(__DIR__, 3);

        self::assertSame([
            'binary' => $packageRoot . '/bin/dev-tools',
            'autoload' => $packageRoot . '/vendor/autoload.php',
            'package_root' => $packageRoot,
            'project_root' => $workingDirectory,
            'version' => '2.0.0',
        ], $metadata);
        self::assertSame($workingDirectory, getcwd());
    }

    /**
     * @return void
     */
    #[Test]
    public function runtimeWillEmitJsonByDefault(): void
    {
        $versionChecker = $this->prophesize(VersionCheckerInterface::class);
        $versionChecker->getCurrentVersion()
            ->willReturn('dev-main');
        $tester = new CommandTester(new RuntimeCommand($versionChecker->reveal()));

        self::assertSame(RuntimeCommand::SUCCESS, $tester->execute([]));
        self::assertSame('dev-main', json_decode($tester->getDisplay(), true)['version']);
    }

    /**
     * @return void
     */
    #[Test]
    public function globalWorkingDirectoryWillSelectConsumerWithoutChangingRuntimePaths(): void
    {
        $directory = sys_get_temp_dir() . '/runtime-consumer-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $directory = realpath($directory);

        try {
            $packageRoot = \dirname(__DIR__, 3);
            $process = new Process([
                \PHP_BINARY,
                $packageRoot . '/bin/dev-tools',
                '--working-dir=' . $directory,
                'runtime',
                '--json',
                '--no-interaction',
                '--no-ansi',
            ], $packageRoot);
            $process->mustRun();
            $metadata = json_decode($process->getOutput(), true);

            self::assertSame($directory, $metadata['project_root']);
            self::assertSame($packageRoot, $metadata['package_root']);
            self::assertSame($packageRoot . '/bin/dev-tools', $metadata['binary']);
            self::assertSame($packageRoot . '/vendor/autoload.php', $metadata['autoload']);
            self::assertIsString($metadata['version']);
        } finally {
            $filesystem = new Filesystem();
            $filesystem->remove($directory);
        }
    }
}
