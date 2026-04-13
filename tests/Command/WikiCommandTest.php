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

use FastForward\DevTools\Console\Command\WikiCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

#[CoversClass(WikiCommand::class)]
final class WikiCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return WikiCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'wiki';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Generates API documentation in Markdown format.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command generates API documentation in Markdown format using phpDocumentor.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunProcessWithPhpdocMarkdownArguments(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpdoc')
                && str_contains($commandLine, '--target')
                && str_contains($commandLine, '.github/wiki');
        });

        self::assertSame(WikiCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->willRunProcessWithCallback(static fn(): bool => true, false);

        self::assertSame(WikiCommand::FAILURE, $this->invokeExecute());
    }
}
