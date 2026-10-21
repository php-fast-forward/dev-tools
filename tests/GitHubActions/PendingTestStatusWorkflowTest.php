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
use Symfony\Component\Yaml\Yaml;
use function Safe\fclose;
use function Safe\fwrite;
use function Safe\proc_open;
use function Safe\stream_get_contents;

#[CoversNothing]
final class PendingTestStatusWorkflowTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidRuns(): iterable
    {
        yield 'wrong ID' => [['id' => 8], 'Workflow run ID does not match the current run.'];
        yield 'missing ID' => [['id' => null], 'Workflow run ID does not match the current run.'];
        yield 'wrong repository' => [['repository' => ['full_name' => 'other/repo']], 'Workflow repository does not match the current repository.'];
        yield 'missing repository' => [['repository' => null], 'Workflow repository does not match the current repository.'];
        yield 'string attempt' => [['run_attempt' => '1'], 'Workflow run attempt is not an integer.'];
        yield 'missing attempt' => [['run_attempt' => null], 'Workflow run attempt is not an integer.'];
        yield 'different attempt' => [['run_attempt' => 2], 'Workflow run attempt does not match the current attempt.'];
        foreach (['queued', 'completed', 'cancelled', null, "untrusted\nTOKEN_MARKER"] as $status) {
            yield 'status ' . ($status ?? 'missing') => [['status' => $status], 'Workflow run status is not in_progress.'];
        }
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[Test]
    #[DataProvider('invalidRuns')]
    public function pendingPublisherWillExplainEachRejectedRunWithoutWriting(array $overrides, string $diagnostic): void
    {
        [$code, $output] = $this->executePublisher($overrides);

        self::assertNotSame(0, $code);
        self::assertStringContainsString($diagnostic, $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
        self::assertStringNotContainsString('TOKEN_MARKER', $output);
    }

    #[Test]
    public function pendingPublisherWillKeepTheValidRunPublishingPath(): void
    {
        [$code, $output] = $this->executePublisher([]);

        self::assertSame(0, $code, $output);
        self::assertSame(3, substr_count($output, 'STATUS_WRITE'));
    }

    /**
     * Execute the actual embedded program with only its GitHub transport substituted.
     *
     * @param array<string, mixed> $overrides
     * @return array{int, string}
     */
    private function executePublisher(array $overrides): array
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/tests.yml');
        $shell = $workflow['jobs']['publish_pending_statuses']['steps'][0]['run'];
        self::assertIsString($shell);
        $program = substr($shell, strpos($shell, '<?php'));
        $program = substr($program, 0, strrpos($program, 'PHP'));
        $start = strpos($program, 'function githubApi(');
        $end = strpos($program, '$repository = getenv');
        self::assertIsInt($start);
        self::assertIsInt($end);
        $run = array_replace([
            'id' => 7,
            'repository' => ['full_name' => 'example/repo'],
            'run_attempt' => 1,
            'status' => 'in_progress',
        ], $overrides);
        $transport = 'function githubApi(array $arguments): array {'
            . 'if ($arguments[0] === "--method") { echo "STATUS_WRITE\\n"; return []; }'
            . 'return ' . var_export($run, true) . '; }' . "\n";
        $program = substr($program, 0, $start) . $transport . substr($program, $end);
        $process = proc_open([PHP_BINARY], [['pipe', 'r'], ['pipe', 'w'], ['redirect', 1]], $pipes, null, array_merge(getenv(), [
            'GITHUB_REPOSITORY' => 'example/repo',
            'GITHUB_RUN_ID' => '7',
            'GITHUB_RUN_ATTEMPT' => '1',
            'TARGET_SHA' => str_repeat('a', 40),
            'TARGET_URL' => 'https://github.com/example/repo/actions/runs/7',
            'TEST_MATRIX' => '{"php-version":["8.3","8.4","8.5"]}',
        ]));
        self::assertIsResource($process);
        fwrite($pipes[0], $program);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        self::assertIsString($output);

        return [proc_close($process), $output];
    }
}
