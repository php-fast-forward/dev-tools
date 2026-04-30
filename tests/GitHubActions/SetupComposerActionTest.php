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

namespace FastForward\DevTools\Tests\GitHubActions;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\Process;

use function Safe\chmod;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\realpath;
use function Safe\rmdir;
use function Safe\unlink;

#[CoversNothing]
final class SetupComposerActionTest extends TestCase
{
    private const string ACTION_PATH = __DIR__ . '/../../.github/actions/php/setup-composer';

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/setup-composer-action-test-' . bin2hex(random_bytes(4));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (! is_dir($this->workspace)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->workspace, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($this->workspace);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillPreferTheConsumerLocalInstallation(): void
    {
        $this->createInstalledRuntimeFiles($this->workspace);
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('local', $outputs['source']);
        self::assertSame('false', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/vendor/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillFallbackToTheWorkflowSourceWhenTheConsumerDoesNotInstallDevTools(): void
    {
        $this->createRepositoryRuntimeFiles($this->workspace . '/.dev-tools-actions');
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('workflow', $outputs['source']);
        self::assertSame('true', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillPreferTheWorkspaceRootRepositoryCheckout(): void
    {
        $this->createRepositoryRuntimeFiles($this->workspace);
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('local', $outputs['source']);
        self::assertSame('false', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillIgnoreAnUnrelatedWorkspaceRepositoryBinary(): void
    {
        $this->createRepositoryRuntimeFiles($this->workspace, 'example/consumer');
        $this->createRepositoryRuntimeFiles($this->workspace . '/.dev-tools-actions');
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('workflow', $outputs['source']);
        self::assertSame('true', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillIgnoreAnUnrelatedInstalledBinary(): void
    {
        $this->createInstalledRuntimeFiles($this->workspace, 'example/consumer');
        $this->createRepositoryRuntimeFiles($this->workspace . '/.dev-tools-actions');
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('workflow', $outputs['source']);
        self::assertSame('true', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function exposeRuntimeWillPublishWrapperAndEnvironmentVariablesForTheWorkflowFallback(): void
    {
        $this->createRepositoryRuntimeFiles($this->workspace . '/.dev-tools-actions');
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();
        $runnerTemp = $this->workspace . '/runner-temp';
        mkdir($runnerTemp, 0o777, true);

        $this->runActionScript('expose-dev-tools-runtime.sh', [
            'GITHUB_ENV' => $files['env'],
            'GITHUB_OUTPUT' => $files['output'],
            'GITHUB_PATH' => $files['path'],
            'RUNNER_TEMP' => $runnerTemp,
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);
        $environment = $this->parseKeyValueFile($files['env']);
        $pathEntries = array_filter(explode("\n", trim(file_get_contents($files['path']))));

        self::assertSame('workflow', $outputs['source']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/autoload.php', $outputs['autoload']);
        self::assertSame($outputs['binary'], $environment['DEV_TOOLS_BINARY']);
        self::assertSame($outputs['autoload'], $environment['DEV_TOOLS_AUTOLOAD']);
        self::assertSame($outputs['autoload'], $environment['DEV_TOOLS_AUTO_RESOLVE_AUTOLOAD']);
        self::assertSame('workflow', $environment['DEV_TOOLS_RUNTIME_SOURCE']);
        self::assertContains($runnerTemp . '/dev-tools-runtime/bin', $pathEntries);

        $wrapper = $outputs['command'];
        $process = new Process([$wrapper, 'wiki', '--target=.github/wiki'], $this->workspace);
        $process->mustRun();

        self::assertSame("dev-tools:wiki --target=.github/wiki\n", $process->getOutput());
    }

    /**
     * @param string $runtimeRoot
     * @param string $packageName
     *
     * @return void
     */
    private function createInstalledRuntimeFiles(
        string $runtimeRoot,
        string $packageName = 'fast-forward/dev-tools'
    ): void {
        mkdir($runtimeRoot . '/vendor/bin', 0o777, true);
        mkdir($runtimeRoot . '/vendor/' . $packageName, 0o777, true);
        file_put_contents(
            $runtimeRoot . '/vendor/bin/dev-tools',
            "#!/usr/bin/env bash\nprintf 'dev-tools:%s\\n' \"\$*\"\n",
        );
        chmod($runtimeRoot . '/vendor/bin/dev-tools', 0o755);
        file_put_contents(
            $runtimeRoot . '/vendor/' . $packageName . '/composer.json',
            \sprintf("{\n    \"name\": \"%s\"\n}\n", $packageName)
        );
        file_put_contents($runtimeRoot . '/vendor/autoload.php', "<?php\n");
    }

    /**
     * @param string $packageName
     * @param string $runtimeRoot
     *
     * @return void
     */
    private function createRepositoryRuntimeFiles(
        string $runtimeRoot,
        string $packageName = 'fast-forward/dev-tools'
    ): void {
        mkdir($runtimeRoot . '/bin', 0o777, true);
        mkdir($runtimeRoot . '/vendor', 0o777, true);
        file_put_contents(
            $runtimeRoot . '/bin/dev-tools',
            "#!/usr/bin/env bash\nprintf 'dev-tools:%s\\n' \"\$*\"\n",
        );
        chmod($runtimeRoot . '/bin/dev-tools', 0o755);
        file_put_contents($runtimeRoot . '/composer.json', \sprintf("{\n    \"name\": \"%s\"\n}\n", $packageName));
        file_put_contents($runtimeRoot . '/vendor/autoload.php', "<?php\n");
    }

    /**
     * @return array{env: string, output: string, path: string}
     */
    private function createGitHubActionFiles(): array
    {
        $directory = $this->workspace . '/.github-action-files';

        mkdir($directory, 0o777, true);

        return [
            'env' => $directory . '/github-env',
            'output' => $directory . '/github-output',
            'path' => $directory . '/github-path',
        ];
    }

    /**
     * @param string $script
     * @param array<string, string> $environment
     *
     * @return void
     */
    private function runActionScript(string $script, array $environment = []): void
    {
        $process = new Process(
            ['bash', self::ACTION_PATH . '/' . $script],
            $this->workspace,
            $environment + [
                'INPUT_DEV_TOOLS_SOURCE_DIRECTORY' => '.dev-tools-actions',
            ],
        );

        $process->mustRun();
    }

    /**
     * @param string $path
     *
     * @return array<string, string>
     */
    private function parseKeyValueFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $entries = [];

        foreach (array_filter(explode("\n", trim(file_get_contents($path)))) as $line) {
            [$key, $value] = explode('=', $line, 2);
            $entries[$key] = $value;
        }

        return $entries;
    }
}
