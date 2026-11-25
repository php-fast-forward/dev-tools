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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use function Safe\fclose;
use function Safe\json_encode;
use function Safe\proc_open;
use function Safe\stream_get_contents;

#[CoversNothing]
final class RequiredTestStatusWorkflowTest extends TestCase
{
    #[Test]
    public function publisherWaitsForCompleteCurrentAttemptAcrossAllPages(): void
    {
        $success = self::job('8.3', 2);
        $oldSuccess = self::job('8.5', 1);
        $nullConclusion = self::job('8.4', 2, 'completed', null);
        $inProgress = self::job('8.5', 2, 'in_progress', null);
        $failed = self::job('8.5', 2, 'completed', 'failure');

        [$code, $output] = $this->executePublisher([
            [['jobs' => [$success, self::job('8.4', 1), $oldSuccess]]],
            [['jobs' => [$success, $oldSuccess, $inProgress]]],
            [['jobs' => [$success, $oldSuccess, $nullConclusion, $inProgress]]],
            [['jobs' => [$success, $oldSuccess]], ['jobs' => [self::job('8.4', 2), $failed]]],
        ], 'failure');

        self::assertSame(0, $code, $output);
        self::assertSame(4, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString('Missing current-attempt workflow job for 8.5', $output);
        self::assertStringContainsString('Incomplete current-attempt workflow job for 8.5', $output);
        self::assertSame(3, substr_count($output, 'STATUS_WRITE'));
        self::assertLessThan(strpos($output, 'STATUS_WRITE'), strrpos($output, 'JOBS_READ'));
        self::assertStringContainsString('state=success|Run Tests (8.3)', $output);
        self::assertStringContainsString('state=success|Run Tests (8.4)', $output);
        self::assertStringContainsString('state=failure|Run Tests (8.5)', $output);
    }

    #[Test]
    public function publisherStopsAfterEightSnapshotsWithoutPublishingAnOlderSuccess(): void
    {
        [$code, $output] = $this->executePublisher([
            [['jobs' => [self::job('8.3', 1), self::job('8.4', 1), self::job('8.5', 1), self::job('8.5', 2, 'queued', null)]]],
        ]);

        self::assertSame(75, $code, $output);
        self::assertSame(8, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString('Incomplete current-attempt workflow job for 8.5', $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
    }

    #[Test]
    public function publisherWillNotMirrorAnEntirelyGreenCurrentAttemptWhenTheDependencyFailed(): void
    {
        [$code, $output] = $this->executePublisher([
            [['jobs' => [self::job('8.3', 2), self::job('8.4', 2), self::job('8.5', 2)]]],
        ], 'failure');

        self::assertSame(75, $code, $output);
        self::assertSame(8, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString('Workflow jobs do not reflect the test matrix result yet.', $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
    }

    #[Test]
    public function publisherWaitsWhenACurrentAttemptFailureContradictsASuccessfulDependency(): void
    {
        [$code, $output] = $this->executePublisher([
            [['jobs' => [self::job('8.3', 2), self::job('8.4', 2), self::job('8.5', 2, 'completed', 'failure')]]],
            [['jobs' => [self::job('8.3', 2), self::job('8.4', 2), self::job('8.5', 2)]]],
        ]);

        self::assertSame(0, $code, $output);
        self::assertSame(2, substr_count($output, 'JOBS_READ'));
        self::assertSame(3, substr_count($output, 'STATUS_WRITE'));
        self::assertStringContainsString('Workflow jobs do not reflect the test matrix result yet.', $output);
        self::assertStringContainsString('state=success|Run Tests (8.5)', $output);
        self::assertStringNotContainsString('state=failure', $output);
    }

    #[Test]
    public function publisherOnlyRerunCannotMirrorPreviouslyCompletedMatrixJobs(): void
    {
        [$code, $output] = $this->executePublisher([
            [['jobs' => [self::job('8.3', 1), self::job('8.4', 1), self::job('8.5', 1)]]],
        ]);

        self::assertSame(75, $code, $output);
        self::assertSame(8, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString('Missing current-attempt workflow job for 8.5', $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function incompleteLegVisibility(): iterable
    {
        yield 'missing from every snapshot' => [false];
        yield 'visible incomplete and later disappears' => [true];
    }

    #[Test]
    #[DataProvider('incompleteLegVisibility')]
    public function publisherWillNotSubstituteAnOlderSuccessEvenWhenAnotherFailureMatchesTheAggregate(
        bool $initiallyVisible
    ): void {
        $stale = [self::job('8.3', 2), self::job('8.4', 2, 'completed', 'failure'), self::job('8.5', 1)];
        $snapshots = [];
        if ($initiallyVisible) {
            $snapshots[] = [['jobs' => [...$stale, self::job('8.5', 2, 'in_progress', null)]]];
        }
        $snapshots[] = [['jobs' => $stale]];

        [$code, $output] = $this->executePublisher($snapshots, 'failure');

        self::assertSame(75, $code, $output);
        self::assertSame(8, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString('Missing current-attempt workflow job for 8.5', $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidSnapshots(): iterable
    {
        $job = self::job('8.5', 2);
        yield 'ambiguous' => [[['jobs' => [$job, $job]]], 'Ambiguous current-attempt workflow job for 8.5'];
        yield 'wrong run' => [[['jobs' => [array_replace($job, ['run_id' => 99])]]], 'Invalid workflow job attempt for 8.5'];
        yield 'superseding attempt' => [[['jobs' => [self::job('8.5', 3)]]], 'Invalid workflow job attempt for 8.5'];
        yield 'malformed response' => [[['jobs' => false]], 'Malformed workflow jobs response.'];
        yield 'invalid result' => [[['jobs' => [array_replace($job, ['conclusion' => false])]]], 'Invalid current-attempt workflow job result for 8.5'];
    }

    /**
     * @param array<array-key, mixed> $snapshot
     */
    #[Test]
    #[DataProvider('invalidSnapshots')]
    public function publisherRejectsInvalidEvidenceWithoutWaitingOrWriting(array $snapshot, string $error): void
    {
        [$code, $output] = $this->executePublisher([$snapshot]);

        self::assertNotSame(0, $code);
        self::assertSame(1, substr_count($output, 'JOBS_READ'));
        self::assertStringContainsString($error, $output);
        self::assertStringNotContainsString('STATUS_WRITE', $output);
    }

    /**
     * @return array{name: string, run_id: int, run_attempt: int, status: string, conclusion: null|string}
     */
    private static function job(string $version, int $attempt, string $status = 'completed', ?string $conclusion = 'success'): array
    {
        return [
            'name' => "tests / Run Tests ($version)",
            'run_id' => 7,
            'run_attempt' => $attempt,
            'status' => $status,
            'conclusion' => $conclusion,
        ];
    }

    /**
     * Execute the exact GitHub Actions shell step with deterministic API snapshots.
     *
     * @param list<array<array-key, mixed>> $snapshots
     * @return array{int, string}
     */
    private function executePublisher(array $snapshots, string $testResult = 'success'): array
    {
        $workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/tests.yml');
        $shell = $workflow['jobs']['publish_required_statuses']['steps'][0]['run'];
        self::assertIsString($shell);
        $filesystem = new Filesystem();
        $directory = $filesystem->tempnam(sys_get_temp_dir(), 'status-workflow-');
        $filesystem->remove($directory);
        $filesystem->mkdir($directory);

        try {
            foreach ($snapshots as $index => $snapshot) {
                $filesystem->dumpFile($directory . '/jobs-' . $index, json_encode($snapshot));
            }
            $filesystem->dumpFile($directory . '/counter', '0');
            $filesystem->dumpFile($directory . '/publish', $shell);
            $filesystem->dumpFile($directory . '/sleep', "#!/bin/bash\nexit 0\n");
            $filesystem->symlink(PHP_BINARY, $directory . '/php');
            $filesystem->dumpFile($directory . '/gh', <<<'BASH'
                #!/bin/bash
                set -eu
                [[ "$1" == api ]]
                if [[ "$2" == --paginate && "$3" == --slurp && "$4" == 'repos/example/repo/actions/runs/7/jobs?filter=all&per_page=100' ]]; then
                  echo JOBS_READ >&2
                  counter=$(<"$RUNNER_TEMP/counter")
                  printf '%s' "$((counter + 1))" > "$RUNNER_TEMP/counter"
                  if ((counter >= FIXTURE_COUNT)); then
                    counter=$((FIXTURE_COUNT - 1))
                  fi
                  cat "$RUNNER_TEMP/jobs-$counter"
                elif [[ "$2" == --method && "$3" == POST && "$4" == "repos/example/repo/statuses/$TARGET_SHA" ]]; then
                  printf 'STATUS_WRITE %s|%s\n' "$6" "${8#context=}"
                else
                  exit 1
                fi
                BASH);
            $filesystem->chmod([$directory . '/gh', $directory . '/sleep'], 0755);
            $process = proc_open(
                'bash --noprofile --norc -eo pipefail ' . escapeshellarg($directory . '/publish'),
                [['pipe', 'r'], ['pipe', 'w'], ['redirect', 1]],
                $pipes,
                null,
                array_merge(getenv(), [
                    'PATH' => $directory . ':' . getenv('PATH'),
                    'RUNNER_TEMP' => $directory,
                    'FIXTURE_COUNT' => (string) count($snapshots),
                    'GITHUB_REPOSITORY' => 'example/repo',
                    'GITHUB_RUN_ID' => '7',
                    'GITHUB_RUN_ATTEMPT' => '2',
                    'TARGET_SHA' => str_repeat('a', 40),
                    'TARGET_URL' => 'https://github.com/example/repo/actions/runs/7',
                    'TEST_RESULT' => $testResult,
                    'TEST_MATRIX' => '{"php-version":["8.3","8.4","8.5"]}',
                ]),
            );
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            return [proc_close($process), $output];
        } finally {
            $filesystem->remove($directory);
        }
    }
}
