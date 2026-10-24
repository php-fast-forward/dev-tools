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
use function Safe\json_encode;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

#[CoversNothing]
final class RetryTransientFailuresActionTest extends TestCase
{
    private const string ACTION_PATH = __DIR__ . '/../../.github/actions/github/retry-transient-failures';

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/retry-transient-failures-action-test-' . bin2hex(random_bytes(4));
        mkdir($this->workspace, 0o777, true);
        mkdir($this->workspace . '/bin', 0o777, true);
        mkdir($this->workspace . '/logs', 0o777, true);
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
    public function actionWillSkipGracefullyWhenAFailedJobLogCannotBeDownloaded(): void
    {
        $this->writeJobsJson([
            'jobs' => [
                [
                    'id' => 42,
                    'name' => 'maintenance / Publish Wiki Master',
                    'conclusion' => 'failure',
                ],
            ],
        ]);
        $this->writeLogFixture(42, '401', '');
        $this->createMockExecutables();

        $outputs = $this->runAction();

        self::assertSame('skipped-uninspectable-logs', $outputs['status']);
        self::assertStringContainsString('maintenance / Publish Wiki Master', $outputs['summary']);
        self::assertStringContainsString('401', $outputs['summary']);
        self::assertFileDoesNotExist($this->workspace . '/rerun-requested');
    }

    /**
     * @return void
     */
    #[Test]
    public function actionWillRequestARerunWhenEveryFailedJobMatchesATransientSignature(): void
    {
        $this->writeJobsJson([
            'jobs' => [
                [
                    'id' => 99,
                    'name' => 'Update Wiki Preview',
                    'conclusion' => 'failure',
                ],
            ],
        ]);
        $this->writeLogFixture(
            99,
            '200',
            "fatal: unable to access 'https://github.com/php-fast-forward/dev-tools': The requested URL returned error: 500\n"
        );
        $this->createMockExecutables();

        $outputs = $this->runAction();

        self::assertSame('rerun-requested', $outputs['status']);
        self::assertStringContainsString('Update Wiki Preview', $outputs['summary']);
        self::assertFileExists($this->workspace . '/rerun-requested');
    }

    /**
     * @param array<string, mixed> $jobs
     *
     * @return void
     */
    private function writeJobsJson(array $jobs): void
    {
        file_put_contents(
            $this->workspace . '/jobs.json',
            json_encode($jobs, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param int $jobId
     * @param string $statusCode
     * @param string $body
     *
     * @return void
     */
    private function writeLogFixture(int $jobId, string $statusCode, string $body): void
    {
        file_put_contents($this->workspace . '/logs/' . $jobId . '.status', $statusCode);
        file_put_contents($this->workspace . '/logs/' . $jobId . '.body', $body);
    }

    /**
     * @return void
     */
    private function createMockExecutables(): void
    {
        file_put_contents(
            $this->workspace . '/bin/gh',
            <<<'BASH'
                #!/usr/bin/env bash
                set -euo pipefail

                if [ "${1:-}" != "api" ]; then
                    echo "Unexpected gh command: $*" >&2
                    exit 1
                fi

                shift

                if [ "${1:-}" = "-X" ]; then
                    method="${2:-}"
                    shift 2
                else
                    method="GET"
                fi

                endpoint="${1:-}"

                case "${method}:${endpoint}" in
                    GET:repos/php-fast-forward/dev-tools/actions/runs/123/jobs?per_page=100)
                        cat "${MOCK_JOBS_FILE}"
                        ;;
                    POST:repos/php-fast-forward/dev-tools/actions/runs/123/rerun-failed-jobs)
                        touch "${MOCK_RERUN_FILE}"
                        ;;
                    *)
                        echo "Unexpected gh api endpoint: ${method}:${endpoint}" >&2
                        exit 1
                        ;;
                esac
                BASH
            ,
        );
        chmod($this->workspace . '/bin/gh', 0o755);

        file_put_contents(
            $this->workspace . '/bin/curl',
            <<<'BASH'
                #!/usr/bin/env bash
                set -euo pipefail

                output_file=""
                url=""

                while [ "$#" -gt 0 ]; do
                    case "$1" in
                        -o)
                            output_file="$2"
                            shift 2
                            ;;
                        -w)
                            shift 2
                            ;;
                        -H)
                            shift 2
                            ;;
                        -s|-S|-L)
                            shift
                            ;;
                        *)
                            url="$1"
                            shift
                            ;;
                    esac
                done

                job_id="${url##*/actions/jobs/}"
                job_id="${job_id%/logs}"
                status_file="${MOCK_LOG_DIR}/${job_id}.status"
                body_file="${MOCK_LOG_DIR}/${job_id}.body"

                cp "${body_file}" "${output_file}"
                cat "${status_file}"
                BASH
            ,
        );
        chmod($this->workspace . '/bin/curl', 0o755);
    }

    /**
     * @return array<string, string>
     */
    private function runAction(): array
    {
        $outputFile = $this->workspace . '/github-output';
        $process = new Process(
            ['bash', self::ACTION_PATH . '/run.sh'],
            $this->workspace,
            [
                'GH_TOKEN' => 'test-token',
                'GITHUB_OUTPUT' => $outputFile,
                'GITHUB_REPOSITORY' => 'php-fast-forward/dev-tools',
                'INPUT_MAX_RUN_ATTEMPTS' => '2',
                'INPUT_RUN_ATTEMPT' => '1',
                'INPUT_RUN_ID' => '123',
                'INPUT_WORKFLOW_NAME' => 'Maintain Wiki',
                'MOCK_JOBS_FILE' => $this->workspace . '/jobs.json',
                'MOCK_LOG_DIR' => $this->workspace . '/logs',
                'MOCK_RERUN_FILE' => $this->workspace . '/rerun-requested',
                'PATH' => $this->workspace . '/bin:' . getenv('PATH'),
            ],
        );

        $process->mustRun();

        return $this->parseGitHubOutputFile($outputFile);
    }

    /**
     * @param string $path
     *
     * @return array<string, string>
     */
    private function parseGitHubOutputFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $entries = [];
        $lines = explode("\n", trim(file_get_contents($path)));
        $counter = \count($lines);

        for ($index = 0; $index < $counter; ++$index) {
            $line = $lines[$index];

            if (str_contains($line, '<<')) {
                [$key, $delimiter] = explode('<<', $line, 2);
                $value = [];
                ++$index;

                while ($index < \count($lines) && $lines[$index] !== $delimiter) {
                    $value[] = $lines[$index];
                    ++$index;
                }

                $entries[$key] = implode("\n", $value);

                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $entries[$key] = $value;
        }

        return $entries;
    }
}
