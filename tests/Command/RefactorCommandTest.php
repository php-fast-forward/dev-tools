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

use FastForward\DevTools\Command\RefactorCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(RefactorCommand::class)]
final class RefactorCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return RefactorCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'refactor';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Runs Rector for code refactoring.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs Rector to refactor your code.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigFile(RefactorCommand::CONFIG);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithLocalConfigWillRunRectorProcessWithDevToolsConfigFile(): void
    {
        $this->withConfigFile(RefactorCommand::CONFIG, true);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            $path = getcwd() . '/' . RefactorCommand::CONFIG;

            return str_contains($commandLine, 'vendor/bin/rector')
                && str_contains($commandLine, 'process')
                && str_contains($commandLine, '--config')
                && str_contains($commandLine, $path)
                && str_contains($commandLine, '--dry-run');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithoutLocalConfigWillRunRectorProcessWithDevToolsConfigFile(): void
    {
        $this->withConfigFile(RefactorCommand::CONFIG);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();
            $path = \dirname(__DIR__, 2) . '/' . RefactorCommand::CONFIG;

            return str_contains($commandLine, 'vendor/bin/rector')
                && str_contains($commandLine, 'process')
                && str_contains($commandLine, '--config')
                && str_contains($commandLine, $path)
                && str_contains($commandLine, '--dry-run');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithFixOptionWillRunRectorProcessWithoutDryRunOption(): void
    {
        $this->input->getOption('fix')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $this->withConfigFile(RefactorCommand::CONFIG, true);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/rector')
                && str_contains($commandLine, 'process')
                && str_contains($commandLine, '--config')
                && str_contains($commandLine, getcwd() . '/' . RefactorCommand::CONFIG)
                && ! str_contains($commandLine, '--dry-run');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->willRunProcessWithCallback(static fn(): true => true, false);

        self::assertSame(RefactorCommand::FAILURE, $this->invokeExecute());
    }
}
