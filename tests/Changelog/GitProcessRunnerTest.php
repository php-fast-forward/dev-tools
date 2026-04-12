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

use FastForward\DevTools\Changelog\GitProcessRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitProcessRunner::class)]
final class GitProcessRunnerTest extends TestCase
{
    private GitProcessRunner $runner;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->runner = new GitProcessRunner();
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillExecuteGitCommandAndReturnTrimmedOutput(): void
    {
        $output = $this->runner->run(['git', 'version'], __DIR__);

        self::assertStringStartsWith('git version', $output);
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillTrimWhitespaceFromOutput(): void
    {
        $output = $this->runner->run(['git', 'rev-parse', '--short', 'HEAD'], __DIR__);

        self::assertSame(7, \strlen($output));
    }
}
