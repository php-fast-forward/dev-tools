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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\CodeStyleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(CodeStyleCommand::class)]
final class CodeStyleCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return CodeStyleCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'code-style';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigFile(CodeStyleCommand::CONFIG);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunComposerUpdateProcess(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'composer')
                && str_contains($commandLine, 'update')
                && str_contains($commandLine, '--lock')
                && str_contains($commandLine, '--quiet');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunComposerNormalizeProcess(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'composer')
                && str_contains($commandLine, 'normalize')
                && str_contains($commandLine, '--dry-run');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithFixOptionWillRunComposerNormalizeProcessWithoutDryRunOptionQuietly(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            $this->input->getOption('fix')
                ->willReturn(true)
                ->shouldBeCalled();

            return str_contains($commandLine, 'composer')
                && str_contains($commandLine, 'normalize')
                && str_contains($commandLine, '--quiet')
                && ! str_contains($commandLine, '--dry-run');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithLocalConfigWillRunCodeStyleProcessWithDevToolsConfigFile(): void
    {
        $this->withConfigFile(CodeStyleCommand::CONFIG, true);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            $path = getcwd() . '/ecs.php';

            return str_contains($commandLine, 'vendor/bin/ecs')
                && str_contains($commandLine, '--config=' . $path)
                && str_contains($commandLine, '--clear-cache');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithoutLocalConfigWillRunCodeStyleProcessWithDevToolsConfigFile(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();
            $path = getcwd() . '/ecs.php';

            return str_contains($commandLine, 'vendor/bin/ecs')
                && str_contains($commandLine, '--config=' . $path)
                && str_contains($commandLine, '--clear-cache');
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithFixOptionWillRunCodeStyleProcessWithoutDryRunOption(): void
    {
        $this->input->getOption('fix')
            ->willReturn(true)
            ->shouldBeCalled();

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();
            $path = getcwd() . '/ecs.php';

            return str_contains($commandLine, 'vendor/bin/ecs')
                && str_contains($commandLine, '--config=' . $path)
                && str_contains($commandLine, '--fix');
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

        self::assertSame(CodeStyleCommand::FAILURE, $this->invokeExecute());
    }
}
