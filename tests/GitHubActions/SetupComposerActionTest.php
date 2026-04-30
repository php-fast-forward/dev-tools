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
        $this->createRuntimeFiles($this->workspace . '/vendor');
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
        mkdir($this->workspace . '/.dev-tools-actions', 0o777, true);
        file_put_contents($this->workspace . '/.dev-tools-actions/composer.json', "{}\n");
        $resolvedWorkspace = realpath($this->workspace);

        $files = $this->createGitHubActionFiles();

        $this->runActionScript('detect-dev-tools-runtime.sh', [
            'GITHUB_OUTPUT' => $files['output'],
        ]);

        $outputs = $this->parseKeyValueFile($files['output']);

        self::assertSame('workflow', $outputs['source']);
        self::assertSame('true', $outputs['needs-fallback']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/bin/dev-tools', $outputs['binary']);
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function exposeRuntimeWillPublishWrapperAndEnvironmentVariablesForTheWorkflowFallback(): void
    {
        mkdir($this->workspace . '/.dev-tools-actions', 0o777, true);
        file_put_contents($this->workspace . '/.dev-tools-actions/composer.json', "{}\n");
        $this->createRuntimeFiles($this->workspace . '/.dev-tools-actions/vendor');
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
        self::assertSame($resolvedWorkspace . '/.dev-tools-actions/vendor/bin/dev-tools', $outputs['binary']);
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
     * @param string $runtimeVendorDirectory
     *
     * @return void
     */
    private function createRuntimeFiles(string $runtimeVendorDirectory): void
    {
        mkdir($runtimeVendorDirectory . '/bin', 0o777, true);
        file_put_contents(
            $runtimeVendorDirectory . '/bin/dev-tools',
            "#!/usr/bin/env bash\nprintf 'dev-tools:%s\\n' \"\$*\"\n",
        );
        chmod($runtimeVendorDirectory . '/bin/dev-tools', 0o755);
        file_put_contents($runtimeVendorDirectory . '/autoload.php', "<?php\n");
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
