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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\chmod;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\mkdir;
use function Safe\realpath;

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
        $this->workspace = realpath($this->workspace);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        new Filesystem()
            ->remove($this->workspace);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillUseTheWorkspaceRootForDevToolsSelfTests(): void
    {
        $this->createRuntime($this->workspace);
        $process = $this->runScript('detect-dev-tools-runtime.sh');
        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $outputs = $this->readValues('output');
        self::assertSame('local', $outputs['source']);
        self::assertSame($this->workspace . '/bin/dev-tools', $outputs['binary']);
        self::assertSame($this->workspace . '/vendor/autoload.php', $outputs['autoload']);
    }

    /**
     * @return void
     */
    #[Test]
    public function detectRuntimeWillIgnoreConsumerInstalledDevToolsAndWorkflowSource(): void
    {
        $this->createRuntime($this->workspace, 'example/consumer');
        $this->createRuntime($this->workspace . '/vendor/fast-forward/dev-tools');
        $this->createRuntime($this->workspace . '/.dev-tools-actions');
        $process = $this->runScript('detect-dev-tools-runtime.sh');
        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        self::assertSame('cpx', $this->readValues('output')['source']);
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerRuntimeWillUsePinnedCpxMetadataAndPreserveArguments(): void
    {
        $runtime = $this->workspace . '/isolated runtime';
        $this->createRuntime($runtime);
        $this->createCpx(json_encode([
            'binary' => $runtime . '/bin/dev-tools',
            'autoload' => $runtime . '/vendor/autoload.php',
        ]));

        $process = $this->runScript('expose-dev-tools-runtime.sh', [
            'INPUT_DEV_TOOLS_VERSION' => '2.0.1',
        ]);
        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $outputs = $this->readValues('output');
        $environment = $this->readValues('env');

        self::assertSame('cpx', $outputs['source']);
        self::assertSame($runtime . '/bin/dev-tools', $outputs['binary']);
        self::assertSame($runtime . '/vendor/autoload.php', $outputs['autoload']);
        self::assertSame($outputs['autoload'], $environment['DEV_TOOLS_AUTOLOAD']);
        self::assertSame($outputs['autoload'], $environment['DEV_TOOLS_AUTO_RESOLVE_AUTOLOAD']);
        self::assertSame($outputs['binary'], $environment['DEV_TOOLS_BINARY']);
        self::assertSame('cpx', $environment['DEV_TOOLS_RUNTIME_SOURCE']);
        self::assertStringContainsString(
            "--skip-local\nfast-forward/dev-tools:2.0.1\nruntime\n--json",
            file_get_contents($this->workspace . '/cpx-arguments')
        );
        self::assertStringContainsString(
            $this->workspace . '/dev-tools-runtime/bin',
            file_get_contents($this->workspace . '/path')
        );

        $command = new Process([$outputs['command'], 'wiki', '--target=directory with spaces'], $this->workspace);
        $command->mustRun();
        self::assertSame("wiki\n--target=directory with spaces\n", $command->getOutput());
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerRuntimeWillRejectRelativeMetadataPaths(): void
    {
        $this->createCpx('{"binary":"vendor/bin/dev-tools","autoload":"vendor/autoload.php"}');
        $process = $this->runScript('expose-dev-tools-runtime.sh');
        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString('Invalid DevTools runtime path', $process->getErrorOutput());
        self::assertFileDoesNotExist($this->workspace . '/env');
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerRuntimeWillRejectMissingAutoload(): void
    {
        $this->createRuntime($this->workspace . '/isolated');
        $this->createCpx(json_encode([
            'binary' => $this->workspace . '/isolated/bin/dev-tools',
            'autoload' => $this->workspace . '/missing.php',
        ]));
        $process = $this->runScript('expose-dev-tools-runtime.sh');
        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString('autoload file was not found', $process->getErrorOutput());
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerRuntimeWillRejectFloatingVersionsBeforeExecutingCpx(): void
    {
        $this->createCpx('{}');
        $process = $this->runScript('expose-dev-tools-runtime.sh', [
            'INPUT_DEV_TOOLS_VERSION' => 'dev-main',
        ]);
        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString('must be an exact published version', $process->getErrorOutput());
        self::assertFileDoesNotExist($this->workspace . '/cpx-arguments');
    }

    /**
     * @return void
     */
    #[Test]
    public function actionWillInstallCpxWithoutInstallingWorkflowSourceDependencies(): void
    {
        $contents = file_get_contents(self::ACTION_PATH . '/action.yml');
        self::assertStringContainsString('tools: cpx', $contents);
        self::assertStringContainsString("default: '2.0.0'", $contents);
        self::assertStringNotContainsString('Install fallback DevTools runtime', $contents);
    }

    /**
     * @param string $root
     * @param string $name
     *
     * @return void
     */
    private function createRuntime(string $root, string $name = 'fast-forward/dev-tools'): void
    {
        mkdir($root . '/bin', 0o777, true);
        mkdir($root . '/vendor', 0o777, true);
        file_put_contents($root . '/composer.json', json_encode([
            'name' => $name,
        ]));
        file_put_contents($root . '/vendor/autoload.php', '<?php');
        file_put_contents($root . '/bin/dev-tools', "#!/usr/bin/env bash\nprintf '%s\\n' \"\$@\"\n");
        chmod($root . '/bin/dev-tools', 0o755);
    }

    /**
     * @param string $metadata
     *
     * @return void
     */
    private function createCpx(string $metadata): void
    {
        mkdir($this->workspace . '/mock-bin', 0o777, true);
        file_put_contents($this->workspace . '/metadata.json', $metadata);
        file_put_contents(
            $this->workspace . '/mock-bin/cpx',
            "#!/usr/bin/env bash\nprintf '%s\\n' \"\$@\" > cpx-arguments\ncat metadata.json\n"
        );
        chmod($this->workspace . '/mock-bin/cpx', 0o755);
    }

    /**
     * @param array<string, string> $environment
     * @param string $script
     */
    private function runScript(string $script, array $environment = []): Process
    {
        $process = new Process(['bash', self::ACTION_PATH . '/' . $script], $this->workspace, $environment + [
            'GITHUB_OUTPUT' => $this->workspace . '/output',
            'GITHUB_ENV' => $this->workspace . '/env',
            'GITHUB_PATH' => $this->workspace . '/path',
            'RUNNER_TEMP' => $this->workspace,
            'PATH' => $this->workspace . '/mock-bin:' . getenv('PATH'),
        ]);
        $process->run();

        return $process;
    }

    /**
     * @param string $file
     *
     * @return array<string, string>
     */
    private function readValues(string $file): array
    {
        $values = [];
        foreach (array_filter(explode("\n", trim(file_get_contents($this->workspace . '/' . $file)))) as $line) {
            [$key, $value] = explode('=', $line, 2);
            $values[$key] = $value;
        }

        return $values;
    }
}
