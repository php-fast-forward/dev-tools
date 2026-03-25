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

namespace FastForward\DevTools\Tests\Command;

use FastForward\DevTools\Command\TestsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(TestsCommand::class)]
final class TestsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return TestsCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'tests';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Runs PHPUnit tests.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs PHPUnit to execute your tests.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigFile(TestsCommand::CONFIG);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithLocalConfigWillRunPhpUnitProcessWithDevToolsConfigFile(): void
    {
        $this->withConfigFile(TestsCommand::CONFIG, true);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpunit')
                && str_contains($commandLine, '--configuration')
                && str_contains($commandLine, getcwd() . '/' . TestsCommand::CONFIG);
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithoutLocalConfigWillRunPhpUnitProcessWithDevToolsConfigFile(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpunit')
                && str_contains($commandLine, '--configuration')
                && str_contains($commandLine, getcwd() . '/' . TestsCommand::CONFIG);
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageWillIncludeCoverageArguments(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-text')
                && str_contains($commandLine, '--coverage-html=');
        });

        $this->input->getOption('coverage')->willReturn('public/coverage');
        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->willRunProcessWithCallback(static fn(): bool => true, false);

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }
}
