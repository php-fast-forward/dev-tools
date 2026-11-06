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

namespace FastForward\DevTools\Tests\GitHooks;

use FastForward\DevTools\GitHooks\HookContentRenderer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\chmod;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\realpath;

/**
 * Packaged hooks MUST execute independently of the DevTools installation path.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversNothing]
final class PackagedHooksTest extends TestCase
{
    private const array ISOLATED_GIT_ENVIRONMENT = [
        'GIT_ALTERNATE_OBJECT_DIRECTORIES' => false,
        'GIT_CONFIG' => false,
        'GIT_CONFIG_PARAMETERS' => false,
        'GIT_CONFIG_COUNT' => false,
        'GIT_OBJECT_DIRECTORY' => false,
        'GIT_DIR' => false,
        'GIT_WORK_TREE' => false,
        'GIT_IMPLICIT_WORK_TREE' => false,
        'GIT_GRAFT_FILE' => false,
        'GIT_INDEX_FILE' => false,
        'GIT_NO_REPLACE_OBJECTS' => false,
        'GIT_REPLACE_REF_BASE' => false,
        'GIT_PREFIX' => false,
        'GIT_INTERNAL_SUPER_PREFIX' => false,
        'GIT_SHALLOW_FILE' => false,
        'GIT_COMMON_DIR' => false,
    ];

    private string $project;

    private Filesystem $filesystem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->project = sys_get_temp_dir() . '/dev-tools-hook-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->project . '/bin');
        $this->project = realpath($this->project);
        $this->git(['init', '--quiet']);
        $this->git(['config', 'user.name', 'Hook Test User']);
        $this->git(['config', 'user.email', 'hook@example.invalid']);
        file_put_contents($this->project . '/staged file.txt', "Staged content\n");
        $this->git(['add', 'staged file.txt']);
        file_put_contents($this->project . '/commit message.txt', "Test commit message\n");
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->project);
    }

    /**
     * @return iterable<string, array{string, bool, int}>
     */
    public static function hookExecutions(): iterable
    {
        foreach (['pre-commit', 'commit-msg'] as $hook) {
            foreach ([true, false] as $config) {
                foreach ([0, 23] as $status) {
                    yield $hook . ($config ? ' with config ' : ' without config ') . $status => [
                        $hook,
                        $config,
                        $status,
                    ];
                }
            }
        }
    }

    /**
     * @param string $hook
     * @param bool $config
     * @param int $status
     *
     * @return void
     */
    #[Test]
    #[DataProvider('hookExecutions')]
    public function packagedHookWillInvokeCpxOnceWithArgumentsDiffAndOriginalExitStatus(
        string $hook,
        bool $config,
        int $status
    ): void {
        $this->writeRunner('bin/cpx');
        if ($config) {
            file_put_contents($this->project . '/grumphp.yml', "grumphp: {}\n");
        }

        $process = $this->executeHook($hook, $status);

        self::assertSame($status, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame("called\n", file_get_contents($this->project . '/calls'));
        self::assertSame(
            $this->expectedArguments($hook, $config, true),
            file_get_contents($this->project . '/arguments')
        );
        self::assertSame($this->gitDiff(), file_get_contents($this->project . '/stdin'));
        self::assertSame($this->project, file_get_contents($this->project . '/working-directory'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function localRunners(): iterable
    {
        foreach (['pre-commit', 'commit-msg'] as $hook) {
            foreach (['grumphp', 'grumphp.phar'] as $runner) {
                yield $hook . ' ' . $runner => [$hook, $runner];
            }
        }
    }

    /**
     * @param string $hook
     * @param string $runner
     *
     * @return void
     */
    #[Test]
    #[DataProvider('localRunners')]
    public function packagedHookWillPreferExecutableLocalRunnerToCpx(string $hook, string $runner): void
    {
        $this->writeRunner('vendor/bin/' . $runner);
        file_put_contents($this->project . '/bin/cpx', "#!/bin/sh\nexit 99\n");
        chmod($this->project . '/bin/cpx', 0o755);
        file_put_contents($this->project . '/grumphp.yml', "grumphp: {}\n");

        $process = $this->executeHook($hook, 17);

        self::assertSame(17, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame("called\n", file_get_contents($this->project . '/calls'));
        self::assertSame(
            $this->expectedArguments($hook, true, false),
            file_get_contents($this->project . '/arguments')
        );
        self::assertSame($this->gitDiff(), file_get_contents($this->project . '/stdin'));
    }

    /**
     * @return void
     */
    #[Test]
    public function packagedHookWillFailClearlyWhenNoRunnerIsAvailable(): void
    {
        $process = $this->executeHook('pre-commit', 0);

        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString(
            'DevTools hooks require CPX or a project-local GrumPHP binary.',
            $process->getErrorOutput()
        );
        self::assertFileDoesNotExist($this->project . '/calls');
    }

    /**
     * @return void
     */
    #[Test]
    public function fixtureProcessesWillIgnoreInheritedRepositoryAndIndexPaths(): void
    {
        $foreignIndex = $this->project . '/foreign-index';
        $foreignContents = "Synthetic index MUST remain unchanged.\n";
        file_put_contents($foreignIndex, $foreignContents);
        $environment = [
            'GIT_DIR' => $this->project . '/foreign.git',
            'GIT_WORK_TREE' => $this->project . '/foreign-work-tree',
            'GIT_COMMON_DIR' => $this->project . '/foreign-common',
            'GIT_INDEX_FILE' => $foreignIndex,
            'GIT_OBJECT_DIRECTORY' => $this->project . '/foreign-objects',
            'GIT_ALTERNATE_OBJECT_DIRECTORIES' => $this->project . '/foreign-alternate-objects',
        ];
        $this->writeRunner('bin/cpx');

        self::assertSame($this->project, trim($this->git(['rev-parse', '--show-toplevel'], $environment)));
        $process = $this->executeHook('pre-commit', 0, $environment);

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame($this->gitDiff(), file_get_contents($this->project . '/stdin'));
        self::assertSame($foreignContents, file_get_contents($foreignIndex));
        self::assertSame("called\n", file_get_contents($this->project . '/calls'));
    }

    /**
     * @param string $hook
     * @param int $status
     * @param array<string, string> $environment
     *
     * @return Process
     */
    private function executeHook(string $hook, int $status, array $environment = []): Process
    {
        $contents = file_get_contents(\dirname(__DIR__, 2) . '/resources/git-hooks/' . $hook);
        file_put_contents($this->project . '/hook', str_replace(
            HookContentRenderer::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER,
            "'./grumphp.yml'",
            $contents,
        ));
        $process = new Process(
            ['/bin/sh', $this->project . '/hook', $this->project . '/commit message.txt'],
            $this->project,
            [
                ...$environment,
                'PATH' => $this->project . '/bin:/usr/bin:/bin',
                'HOOK_CAPTURE_DIR' => $this->project,
                'HOOK_EXIT_STATUS' => (string) $status,
                'GIT_CONFIG_GLOBAL' => '/dev/null',
                'GIT_CONFIG_NOSYSTEM' => '1',
                ...self::ISOLATED_GIT_ENVIRONMENT,
            ],
        );
        $process->run();

        return $process;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function writeRunner(string $path): void
    {
        $this->filesystem->mkdir(\dirname($this->project . '/' . $path));
        file_put_contents($this->project . '/' . $path, <<<'SH'
            #!/bin/sh
            printf 'called\n' >> "$HOOK_CAPTURE_DIR/calls"
            printf '%s\n' "$@" > "$HOOK_CAPTURE_DIR/arguments"
            printf '%s' "$GRUMPHP_GIT_WORKING_DIR" > "$HOOK_CAPTURE_DIR/working-directory"
            cat > "$HOOK_CAPTURE_DIR/stdin"
            exit "$HOOK_EXIT_STATUS"
            SH);
        chmod($this->project . '/' . $path, 0o755);
    }

    /**
     * @param string $hook
     * @param bool $config
     * @param bool $cpx
     *
     * @return string
     */
    private function expectedArguments(string $hook, bool $config, bool $cpx): string
    {
        $arguments = $cpx ? ['--skip-local', 'phpro/grumphp-shim:2.26.0', 'grumphp'] : [];
        if ($config) {
            $arguments[] = '--config';
            $arguments[] = './grumphp.yml';
        }

        $arguments[] = 'git:' . $hook;
        if ('pre-commit' === $hook) {
            $arguments[] = '--skip-success-output';
        } else {
            $arguments[] = '--git-user=Hook Test User';
            $arguments[] = '--git-email=hook@example.invalid';
            $arguments[] = $this->project . '/commit message.txt';
        }

        return implode("\n", $arguments) . "\n";
    }

    /**
     * @return string
     */
    private function gitDiff(): string
    {
        return $this->git(
            [
                '-c',
                'diff.mnemonicprefix=false',
                '-c',
                'diff.noprefix=false',
                '--no-pager',
                'diff',
                '--raw',
                '-r',
                '-p',
                '-m',
                '-M',
                '--full-index',
                '--no-color',
                '--staged',
            ]
        );
    }

    /**
     * @param list<string> $arguments
     * @param array<string, string> $environment
     */
    private function git(array $arguments, array $environment = []): string
    {
        $process = new Process(['git', ...$arguments], $this->project, [
            ...$environment,
            'GIT_CONFIG_GLOBAL' => '/dev/null',
            'GIT_CONFIG_NOSYSTEM' => '1',
            ...self::ISOLATED_GIT_ENVIRONMENT,
        ]);
        $process->mustRun();

        return $process->getOutput();
    }
}
