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
use function Safe\rmdir;
use function Safe\unlink;

#[CoversNothing]
final class RefreshReleaseWikiPointerActionTest extends TestCase
{
    private const string ACTION_PATH = __DIR__ . '/../../.github/actions/wiki/refresh-release-pointer';

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/refresh-release-wiki-pointer-action-test-' . bin2hex(random_bytes(4));
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
    public function refreshWillPublishTheWikiBranchAndExposeTheParentPointerChange(): void
    {
        $workspace = $this->createWorkspaceWithWikiSubmodule();
        $this->createMockDevToolsBinary(true);
        $outputFile = $this->workspace . '/github-output';

        $this->runAction($workspace, $outputFile);

        $outputs = $this->parseKeyValueFile($outputFile);
        $status = $this->runProcess(['git', 'status', '--short', '.github/wiki'], $workspace);

        self::assertSame('true', $outputs['published']);
        self::assertSame('true', $outputs['pointer-changed']);
        self::assertStringContainsString('.github/wiki', $status->getOutput());
    }

    /**
     * @return void
     */
    #[Test]
    public function refreshWillSkipPublicationWhenTheRenderedWikiDoesNotChange(): void
    {
        $workspace = $this->createWorkspaceWithWikiSubmodule();
        $this->createMockDevToolsBinary(false);
        $outputFile = $this->workspace . '/github-output';

        $this->runAction($workspace, $outputFile);

        $outputs = $this->parseKeyValueFile($outputFile);
        $status = $this->runProcess(['git', 'status', '--short', '.github/wiki'], $workspace);

        self::assertSame('false', $outputs['published']);
        self::assertSame('false', $outputs['pointer-changed']);
        self::assertSame('', trim($status->getOutput()));
    }

    /**
     * @return void
     */
    #[Test]
    public function refreshWillIgnoreRemotePointerDriftWhenTheRenderedWikiDoesNotChange(): void
    {
        $workspace = $this->createWorkspaceWithWikiSubmodule();
        $this->advanceWikiRemote();
        $this->createMockDevToolsBinary(false);
        $outputFile = $this->workspace . '/github-output';

        $this->runAction($workspace, $outputFile);

        $outputs = $this->parseKeyValueFile($outputFile);
        $status = $this->runProcess(['git', 'status', '--short', '.github/wiki'], $workspace);

        self::assertSame('false', $outputs['published']);
        self::assertSame('false', $outputs['pointer-changed']);
        self::assertSame('', trim($status->getOutput()));
    }

    /**
     * @return void
     */
    #[Test]
    public function refreshWillRepublishFromTheCheckedInPointerWhenThePublishedWikiBranchAdvanced(): void
    {
        $workspace = $this->createWorkspaceWithWikiSubmodule();
        $this->advanceWikiRemote();
        $this->createMockDevToolsBinary(true);
        $outputFile = $this->workspace . '/github-output';

        $this->runAction($workspace, $outputFile);

        $outputs = $this->parseKeyValueFile($outputFile);
        $status = $this->runProcess(['git', 'status', '--short', '.github/wiki'], $workspace);
        $remoteHead = $this->runProcess(
            ['git', 'rev-parse', 'refs/heads/master'],
            $this->workspace . '/wiki-remote.git',
        );

        self::assertSame('true', $outputs['published']);
        self::assertSame('true', $outputs['pointer-changed']);
        self::assertSame(trim($outputs['publish-sha']), trim($remoteHead->getOutput()));
        self::assertStringContainsString('.github/wiki', $status->getOutput());
    }

    /**
     * @return string
     */
    private function createWorkspaceWithWikiSubmodule(): string
    {
        $wikiRemote = $this->workspace . '/wiki-remote.git';
        $wikiSeed = $this->workspace . '/wiki-seed';
        $workspace = $this->workspace . '/workspace';

        mkdir($wikiSeed, 0o777, true);
        mkdir($workspace, 0o777, true);

        $this->runProcess(['git', 'init', '--bare', $wikiRemote], $this->workspace);
        $this->runProcess(['git', 'init', '--initial-branch=master'], $wikiSeed);
        $this->runProcess(['git', 'config', 'user.name', 'Test User'], $wikiSeed);
        $this->runProcess(['git', 'config', 'user.email', 'test@example.com'], $wikiSeed);
        file_put_contents($wikiSeed . '/README.md', "# Wiki\n");
        $this->runProcess(['git', 'add', 'README.md'], $wikiSeed);
        $this->runProcess(['git', 'commit', '-m', 'Seed wiki'], $wikiSeed);
        $this->runProcess(['git', 'remote', 'add', 'origin', $wikiRemote], $wikiSeed);
        $this->runProcess(['git', 'push', '-u', 'origin', 'master'], $wikiSeed);
        $this->runProcess(['git', 'symbolic-ref', 'HEAD', 'refs/heads/master'], $wikiRemote);

        $this->runProcess(['git', 'init', '--initial-branch=main'], $workspace);
        $this->runProcess(['git', 'config', 'user.name', 'Test User'], $workspace);
        $this->runProcess(['git', 'config', 'user.email', 'test@example.com'], $workspace);
        file_put_contents($workspace . '/composer.json', "{\n    \"name\": \"fast-forward/dev-tools\"\n}\n");
        $this->runProcess(['git', 'add', 'composer.json'], $workspace);
        $this->runProcess(['git', 'commit', '-m', 'Initialize workspace'], $workspace);
        $this->runProcess(
            [
                'git',
                '-c',
                'protocol.file.allow=always',
                'submodule',
                'add',
                '-b',
                'master',
                $wikiRemote,
                '.github/wiki',
            ],
            $workspace,
        );
        $this->runProcess(['git', 'commit', '-am', 'Add wiki submodule'], $workspace);

        return $workspace;
    }

    /**
     * @param bool $shouldChange
     *
     * @return void
     */
    private function createMockDevToolsBinary(bool $shouldChange): void
    {
        $binDirectory = $this->workspace . '/bin';

        mkdir($binDirectory, 0o777, true);

        file_put_contents(
            $binDirectory . '/dev-tools',
            "#!/usr/bin/env bash\nset -euo pipefail\nif [ \"\${1:-}\" != \"wiki\" ]; then\n  echo \"unexpected dev-tools arguments: \$*\" >&2\n  exit 1\nfi\nif [ \"{$shouldChange}\" = \"1\" ]; then\n  printf '# Release wiki refresh\\n' > \"\$PWD/.github/wiki/release-refresh.md\"\nfi\n",
        );
        chmod($binDirectory . '/dev-tools', 0o755);
    }

    /**
     * @return void
     */
    private function advanceWikiRemote(): void
    {
        $wikiSeed = $this->workspace . '/wiki-seed';

        file_put_contents($wikiSeed . '/README.md', "# Wiki\n\nUpdated upstream.\n");
        $this->runProcess(['git', 'add', 'README.md'], $wikiSeed);
        $this->runProcess(['git', 'commit', '-m', 'Advance wiki remote'], $wikiSeed);
        $this->runProcess(['git', 'push', 'origin', 'master'], $wikiSeed);
    }

    /**
     * @param string $workspace
     * @param string $outputFile
     *
     * @return void
     */
    private function runAction(string $workspace, string $outputFile): void
    {
        $process = new Process(
            ['bash', self::ACTION_PATH . '/run.sh'],
            $workspace,
            $this->getIsolatedEnvironment([
                'GITHUB_OUTPUT' => $outputFile,
                'GIT_AUTHOR_NAME' => 'github-actions[bot]',
                'GIT_AUTHOR_EMAIL' => '41898282+github-actions[bot]@users.noreply.github.com',
                'GIT_ALLOW_PROTOCOL' => 'file:https:http',
                'INPUT_COMMIT_MESSAGE' => 'Refresh wiki docs after release',
                'PATH' => $this->workspace . '/bin:' . getenv('PATH'),
            ]),
        );

        $process->mustRun();
    }

    /**
     * @param list<string> $command
     * @param string $workingDirectory
     *
     * @return Process
     */
    private function runProcess(array $command, string $workingDirectory): Process
    {
        if (! is_dir($this->workspace . '/home')) {
            mkdir($this->workspace . '/home', 0o777, true);
        }

        $process = new Process($command, $workingDirectory, $this->getIsolatedEnvironment([
            'GIT_ALLOW_PROTOCOL' => 'file:https:http',
            'GIT_CONFIG_GLOBAL' => '/dev/null',
            'HOME' => $this->workspace . '/home',
        ]));
        $process->mustRun();

        return $process;
    }

    /**
     * @param array<string, string> $environment
     *
     * @return array<string, string|false>
     */
    private function getIsolatedEnvironment(array $environment): array
    {
        return $environment + [
            'GIT_COMMON_DIR' => false,
            'GIT_DIR' => false,
            'GIT_INDEX_FILE' => false,
            'GIT_INTERNAL_SUPER_PREFIX' => false,
            'GIT_PREFIX' => false,
            'GIT_WORK_TREE' => false,
        ];
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
