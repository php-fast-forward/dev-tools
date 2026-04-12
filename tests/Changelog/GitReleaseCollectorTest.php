<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\GitProcessRunnerInterface;
use FastForward\DevTools\Changelog\GitReleaseCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(GitReleaseCollector::class)]
final class GitReleaseCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GitProcessRunnerInterface>
     */
    private ObjectProphecy $gitProcessRunner;

    private GitReleaseCollector $gitReleaseCollector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->gitProcessRunner = $this->prophesize(GitProcessRunnerInterface::class);
        $this->gitReleaseCollector = new GitReleaseCollector($this->gitProcessRunner->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function collectWillReturnReleaseRangesWithFilteredCommitSubjects(): void
    {
        $workingDirectory = '/tmp/project';

        $this->gitProcessRunner->run(
            Argument::that(static fn(array $command): bool => \in_array('for-each-ref', $command, true)),
            $workingDirectory
        )
            ->willReturn("v1.0.0\t2026-04-08\nbeta\t2026-04-09\nv1.1.0\t2026-04-10");
        $this->gitProcessRunner->run(['git', 'log', '--format=%s', '--no-merges', 'v1.0.0'], $workingDirectory)
            ->willReturn("feat: add bootstrap command\nUpdate wiki submodule pointer\n");
        $this->gitProcessRunner->run(['git', 'log', '--format=%s', '--no-merges', 'v1.0.0..v1.1.0'], $workingDirectory)
            ->willReturn("fix: validate unreleased notes\nMerge pull request #10 from feature\n");

        self::assertSame([
            [
                'version' => '1.0.0',
                'tag' => 'v1.0.0',
                'date' => '2026-04-08',
                'commits' => ['feat: add bootstrap command'],
            ],
            [
                'version' => '1.1.0',
                'tag' => 'v1.1.0',
                'date' => '2026-04-10',
                'commits' => ['fix: validate unreleased notes'],
            ],
        ], $this->gitReleaseCollector->collect($workingDirectory));
    }
}
