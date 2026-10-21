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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function Safe\chmod;
use function Safe\fclose;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\proc_open;
use function Safe\rmdir;
use function Safe\stream_get_contents;
use function Safe\unlink;

#[CoversNothing]
final class PagesDeploymentActionTest extends TestCase
{
    /** @return iterable<string, array{string, int, string, string, int, int}> */
    public static function responses(): iterable
    {
        yield 'late propagation' => ['404 404 404 404 404 404 200', 0, '12', '10', 0, 7];
        yield 'immediate success' => ['200', 0, '12', '10', 0, 1];
        yield 'missing deployment' => ['404', 0, '12', '10', 1, 12];
        yield 'broken deployment' => ['500', 0, '12', '10', 1, 12];
        yield 'transfer timeout despite status' => ['200', 28, '12', '10', 1, 12];
        yield 'malformed response' => ['TOKEN_MARKER', 0, '12', '10', 1, 12];
        yield 'total time budget' => ['404', 0, '30', '30', 1, 6];
        yield 'zero attempts' => ['200', 0, '0', '10', 1, 0];
        yield 'unbounded attempts' => ['200', 0, '31', '10', 1, 0];
        yield 'invalid delay' => ['200', 0, '12', 'TOKEN_MARKER', 1, 0];
        yield 'zero delay' => ['200', 0, '12', '0', 1, 0];
        yield 'unbounded delay' => ['200', 0, '12', '31', 1, 0];
    }

    #[Test]
    #[DataProvider('responses')]
    public function deploymentCheckWillPreserveBoundedFailureSemantics(string $responses, int $curlExit, string $attempts, string $delay, int $expectedExit, int $expectedCalls): void
    {
        [$exit, $output, $calls] = $this->runAction($responses, $curlExit, $attempts, $delay);
        self::assertSame($expectedExit, $exit, $output);
        self::assertCount($expectedCalls, $calls);
        self::assertStringNotContainsString('TOKEN_MARKER', $output);
        foreach ($calls as $call) {
            self::assertStringContainsString('--connect-timeout 5 --max-time ', $call);
        }
    }

    #[Test]
    public function deploymentCheckWillVerifyEveryConfiguredPath(): void
    {
        [$exit, $output, $calls] = $this->runAction('200', 0, '12', '10', "/|Index\n/coverage/|Coverage");
        self::assertSame(0, $exit, $output);
        self::assertCount(2, $calls);
        self::assertStringContainsString('https://example.test/coverage/', $calls[1]);
    }

    #[Test]
    public function deploymentChecksWillShareOneBudgetAcrossPaths(): void
    {
        [$exit, $output, $calls] = $this->runAction('404 200 404', 0, '30', '30', "/|Index\n/coverage/|Coverage");
        self::assertSame(1, $exit);
        self::assertCount(7, $calls);
        self::assertStringContainsString('180-second total budget', $output);
    }

    #[Test]
    public function finalTransferWillBeLimitedToTheRemainingBudget(): void
    {
        [$exit, $output, $calls] = $this->runAction('404', 0, '30', '29');
        self::assertSame(1, $exit);
        self::assertCount(7, $calls);
        self::assertMatchesRegularExpression('/--max-time [1-6] /', $calls[6]);
        self::assertStringContainsString('180-second total budget', $output);
    }

    /** @return array{int, string, list<string>} */
    private function runAction(string $responses, int $curlExit, string $attempts, string $delay, string $checks = '/|Index'): array
    {
        $directory = sys_get_temp_dir() . '/pages-check-' . bin2hex(random_bytes(8));
        mkdir($directory);
        try {
            file_put_contents($directory . '/curl', <<<'BASH'
#!/usr/bin/env bash
set -eu
printf '%s\n' "$*" >> "$FIXTURE/calls"
count=$(wc -l < "$FIXTURE/calls")
read -r -a responses <<< "$RESPONSES"
index=$((count-1))
if ((index >= ${#responses[@]})); then index=$((${#responses[@]}-1)); fi
printf '%s' "${responses[$index]}"
echo TOKEN_MARKER >&2
exit "$CURL_EXIT"
BASH);
            chmod($directory . '/curl', 0o700);
            file_put_contents($directory . '/calls', '');
            $script = __DIR__ . '/../../.github/actions/github-pages/verify-deployment/run.sh';
            $command = 'sleep() { SECONDS=$((SECONDS + $1)); }; source ' . escapeshellarg($script);
            $process = proc_open('bash -c ' . escapeshellarg($command), [['pipe', 'r'], ['pipe', 'w'], ['redirect', 1]], $pipes, null, array_merge(getenv(), [
                'PATH' => $directory . ':' . getenv('PATH'),
                'FIXTURE' => $directory,
                'RESPONSES' => $responses,
                'CURL_EXIT' => (string) $curlExit,
                'INPUT_BASE_URL' => 'https://example.test',
                'INPUT_TITLE' => 'Deployment failed',
                'INPUT_CHECKS' => $checks,
                'INPUT_ATTEMPTS' => $attempts,
                'INPUT_DELAY' => $delay,
            ]));
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $exit = proc_close($process);
            $calls = trim(file_get_contents($directory . '/calls'));

            return [$exit, $output, $calls === '' ? [] : explode("\n", $calls)];
        } finally {
            foreach (['curl', 'calls'] as $file) {
                if (is_file($directory . '/' . $file)) { unlink($directory . '/' . $file); }
            }
            rmdir($directory);
        }
    }
}
