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

use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

#[CoversNothing]
final class ResolvePredictableConflictsActionTest extends TestCase
{
    private const string GITLINK_RESOLVER_PATH = __DIR__ . '/../../.github/actions/github/resolve-predictable-conflicts/stage-unmerged-gitlink.sh';

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/resolve-predictable-conflicts-action-test-' . bin2hex(
            random_bytes(4)
        );
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
    public function gitlinkResolverWillStageTheCurrentBranchPointerWithoutMaterializingTheSubmoduleCheckout(): void
    {
        [
            'repository' => $repository,
            'ours-sha' => $oursSha,
        ] = $this->createRepositoryWithUnmergedWikiGitlinkConflict();

        $addProcess = $this->runProcessAllowingFailure(['git', 'add', '.github/wiki'], $repository);
        $unmergedBefore = $this->runProcess(['git', 'ls-files', '-u', '--', '.github/wiki'], $repository);

        self::assertNotSame(0, $addProcess->getExitCode());
        self::assertStringContainsString(".github/wiki\n", $unmergedBefore->getOutput());

        $this->runProcess([self::GITLINK_RESOLVER_PATH, $repository, '.github/wiki'], $this->workspace);

        $unmergedAfter = $this->runProcess(['git', 'ls-files', '-u', '--', '.github/wiki'], $repository);
        $indexEntry = $this->runProcess(['git', 'ls-files', '-s', '--', '.github/wiki'], $repository);

        self::assertSame('', trim($unmergedAfter->getOutput()));
        self::assertSame(\sprintf("160000 %s 0\t.github/wiki\n", $oursSha), $indexEntry->getOutput());
    }

    /**
     * @return array{repository: string, ours-sha: string}
     */
    private function createRepositoryWithUnmergedWikiGitlinkConflict(): array
    {
        $wikiRemote = $this->workspace . '/wiki-remote.git';
        $wikiSeed = $this->workspace . '/wiki-seed';
        $parentRemote = $this->workspace . '/parent-remote.git';
        $parentSeed = $this->workspace . '/parent-seed';
        $repository = $this->workspace . '/repository';

        mkdir($wikiSeed, 0o777, true);
        mkdir($parentSeed, 0o777, true);

        $this->runProcess(['git', 'init', '--bare', $wikiRemote], $this->workspace);
        $this->runProcess(['git', 'init', '--initial-branch=main'], $wikiSeed);
        $this->runProcess(['git', 'config', 'user.name', 'Test User'], $wikiSeed);
        $this->runProcess(['git', 'config', 'user.email', 'test@example.com'], $wikiSeed);
        file_put_contents($wikiSeed . '/README.md', "# Wiki\n");
        $this->runProcess(['git', 'add', 'README.md'], $wikiSeed);
        $this->runProcess(['git', 'commit', '-m', 'Seed wiki'], $wikiSeed);
        $this->runProcess(['git', 'remote', 'add', 'origin', $wikiRemote], $wikiSeed);
        $this->runProcess(['git', 'push', '-u', 'origin', 'main'], $wikiSeed);

        $baseSha = trim($this->runProcess(['git', 'rev-parse', 'HEAD'], $wikiSeed)->getOutput());

        file_put_contents($wikiSeed . '/README.md', "# Wiki\n\nBranch B\n");
        $this->runProcess(['git', 'commit', '-am', 'Advance wiki branch B'], $wikiSeed);
        $this->runProcess(['git', 'push', 'origin', 'HEAD:refs/heads/branch-b'], $wikiSeed);
        $branchBSha = trim($this->runProcess(['git', 'rev-parse', 'HEAD'], $wikiSeed)->getOutput());

        $this->runProcess(['git', 'switch', '--detach', $baseSha], $wikiSeed);
        file_put_contents($wikiSeed . '/README.md', "# Wiki\n\nBranch C\n");
        $this->runProcess(['git', 'commit', '-am', 'Advance wiki branch C'], $wikiSeed);
        $this->runProcess(['git', 'push', 'origin', 'HEAD:refs/heads/branch-c'], $wikiSeed);
        $branchCSha = trim($this->runProcess(['git', 'rev-parse', 'HEAD'], $wikiSeed)->getOutput());

        $this->runProcess(['git', 'init', '--bare', $parentRemote], $this->workspace);
        $this->runProcess(['git', 'init', '--initial-branch=main'], $parentSeed);
        $this->runProcess(['git', 'config', 'user.name', 'Test User'], $parentSeed);
        $this->runProcess(['git', 'config', 'user.email', 'test@example.com'], $parentSeed);
        file_put_contents($parentSeed . '/composer.json', "{\n    \"name\": \"fast-forward/dev-tools\"\n}\n");
        $this->runProcess(['git', 'add', 'composer.json'], $parentSeed);
        $this->runProcess(['git', 'commit', '-m', 'Seed parent repository'], $parentSeed);
        $this->runProcess(
            [
                'git',
                '-c',
                'protocol.file.allow=always',
                'submodule',
                'add',
                '-b',
                'main',
                $wikiRemote,
                '.github/wiki',
            ],
            $parentSeed,
        );
        $this->runProcess(['git', 'commit', '-am', 'Add wiki submodule'], $parentSeed);
        $this->runProcess(['git', 'remote', 'add', 'origin', $parentRemote], $parentSeed);
        $this->runProcess(['git', 'push', '-u', 'origin', 'main'], $parentSeed);

        $this->runProcess(['git', 'switch', '-c', 'feature'], $parentSeed);
        $this->runProcess(['git', 'fetch', 'origin', 'branch-b'], $parentSeed . '/.github/wiki');
        $this->runProcess(['git', 'checkout', $branchBSha], $parentSeed . '/.github/wiki');
        $this->runProcess(['git', 'add', '.github/wiki'], $parentSeed);
        $this->runProcess(['git', 'commit', '-m', 'Point wiki to branch B'], $parentSeed);
        $this->runProcess(['git', 'push', '-u', 'origin', 'feature'], $parentSeed);

        $this->runProcess(['git', 'switch', 'main'], $parentSeed);
        $this->runProcess(['git', 'fetch', 'origin', 'branch-c'], $parentSeed . '/.github/wiki');
        $this->runProcess(['git', 'checkout', $branchCSha], $parentSeed . '/.github/wiki');
        $this->runProcess(['git', 'add', '.github/wiki'], $parentSeed);
        $this->runProcess(['git', 'commit', '-m', 'Point wiki to branch C'], $parentSeed);
        $this->runProcess(['git', 'push', 'origin', 'main'], $parentSeed);

        $this->runProcess(['git', 'clone', '--no-tags', $parentRemote, $repository], $this->workspace);
        $this->runProcess(['git', 'fetch', 'origin', 'feature:refs/remotes/origin/feature'], $repository);

        $mergeProcess = $this->runProcessAllowingFailure(
            ['git', 'merge', '--no-commit', '--no-ff', 'refs/remotes/origin/feature'],
            $repository,
        );

        self::assertNotSame(0, $mergeProcess->getExitCode());
        self::assertStringContainsString(".github/wiki\n", $this->runProcess(
            ['git', 'ls-files', '-u', '--', '.github/wiki'],
            $repository,
        )->getOutput());

        return [
            'repository' => $repository,
            'ours-sha' => $branchCSha,
        ];
    }

    /**
     * @param array<int, string> $command
     * @param string $workingDirectory
     *
     * @return Process
     */
    private function runProcess(array $command, string $workingDirectory): Process
    {
        $process = new Process($command, $workingDirectory);
        $process->mustRun();

        return $process;
    }

    /**
     * @param array<int, string> $command
     * @param string $workingDirectory
     *
     * @return Process
     */
    private function runProcessAllowingFailure(array $command, string $workingDirectory): Process
    {
        $process = new Process($command, $workingDirectory);
        $process->run();

        return $process;
    }
}
