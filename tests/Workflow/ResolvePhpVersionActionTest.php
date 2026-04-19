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

namespace FastForward\DevTools\Tests\Workflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\mkdir;

#[CoversNothing]
final class ResolvePhpVersionActionTest extends TestCase
{
    #[Test]
    public function actionWillUseComposerLockPlatformOverrideFirst(): void
    {
        $result = $this->runResolver(
            ['require' => ['php' => '^8.3'], 'config' => ['platform' => ['php' => '8.4.0']]],
            ['platform-overrides' => ['php' => '8.5.0']],
        );

        self::assertSame('8.5', $result['outputs']['php-version']);
        self::assertSame('composer.lock platform-overrides.php', $result['outputs']['php-version-source']);
        self::assertSame(['php-version' => ['8.5']], $result['matrix']);
    }

    #[Test]
    public function actionWillUseComposerJsonPlatformWhenLockOverrideIsMissing(): void
    {
        $result = $this->runResolver(
            ['config' => ['platform' => ['php' => '8.4.0']]],
            null,
        );

        self::assertSame('8.4', $result['outputs']['php-version']);
        self::assertSame('composer.json config.platform.php', $result['outputs']['php-version-source']);
        self::assertSame(['php-version' => ['8.4', '8.5']], $result['matrix']);
    }

    #[Test]
    public function actionWillBuildMatrixFromComposerRequireConstraint(): void
    {
        $result = $this->runResolver(
            ['require' => ['php' => '^8.4']],
            null,
        );

        self::assertSame('8.4', $result['outputs']['php-version']);
        self::assertSame('composer.json require.php', $result['outputs']['php-version-source']);
        self::assertSame(['php-version' => ['8.4', '8.5']], $result['matrix']);
    }

    #[Test]
    public function actionWillHandleGreaterThanOrEqualConstraint(): void
    {
        $result = $this->runResolver(
            ['require' => ['php' => '>=8.5']],
            null,
        );

        self::assertSame('8.5', $result['outputs']['php-version']);
        self::assertSame(['php-version' => ['8.5']], $result['matrix']);
    }

    #[Test]
    public function actionWillFallbackWhenNoReliableRequirementExists(): void
    {
        $result = $this->runResolver(
            ['require' => ['php' => '<8.3']],
            null,
        );

        self::assertSame('8.3', $result['outputs']['php-version']);
        self::assertSame('fallback', $result['outputs']['php-version-source']);
        self::assertSame(['php-version' => ['8.3', '8.4', '8.5']], $result['matrix']);
        self::assertNotSame('', $result['outputs']['warning']);
    }

    /**
     * @param array<string, mixed> $composerJson
     * @param array<string, mixed>|null $composerLock
     *
     * @return array{outputs: array<string, string>, matrix: array<string, array<int, string>>, stdout: string}
     */
    private function runResolver(array $composerJson, ?array $composerLock): array
    {
        $directory = \sys_get_temp_dir() . '/resolve-php-version-' . uniqid('', true);
        mkdir($directory);
        file_put_contents($directory . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        if (null !== $composerLock) {
            file_put_contents($directory . '/composer.lock', json_encode($composerLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        }

        $outputFile = $directory . '/github-output.txt';

        $process = new Process(
            [
                'python3',
                '-c',
                $this->actionScript(),
            ],
            $directory,
        );
        $process->setEnv(['GITHUB_OUTPUT' => $outputFile]);
        $process->mustRun();

        $outputs = [];

        foreach (explode("\n", trim(file_get_contents($outputFile))) as $line) {
            if ('' === $line) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $outputs[$key] = $value;
        }

        /** @var array<string, array<int, string>> $matrix */
        $matrix = json_decode($outputs['test-matrix'], true);

        return [
            'outputs' => $outputs,
            'matrix' => $matrix,
            'stdout' => $process->getOutput(),
        ];
    }

    private function actionScript(): string
    {
        $actionContents = file_get_contents(\dirname(__DIR__, 2) . '/.github/actions/resolve-php-version/action.yml');

        self::assertIsString($actionContents);
        $lines = explode("\n", $actionContents);
        $start = array_search("        python3 <<'PY'", $lines, true);

        self::assertNotFalse($start);

        $scriptLines = [];

        for ($index = $start + 1, $lineCount = count($lines); $index < $lineCount; ++$index) {
            if ('        PY' === $lines[$index]) {
                return implode("\n", $scriptLines);
            }

            $scriptLines[] = substr($lines[$index], 8);
        }

        self::fail('The resolve-php-version action does not contain the expected Python heredoc terminator.');
    }
}
