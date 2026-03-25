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

use FastForward\DevTools\Command\StandardsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;

#[CoversClass(StandardsCommand::class)]
final class StandardsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    protected function getCommandClass(): string
    {
        return StandardsCommand::class;
    }

    protected function getCommandName(): string
    {
        return 'standards';
    }

    protected function getCommandDescription(): string
    {
        return 'Runs Fast Forward code standards checks.';
    }

    protected function getCommandHelp(): string
    {
        return 'This command runs all Fast Forward code standards checks, including code refactoring, PHPDoc validation, code style checks, documentation generation, and tests execution.';
    }

    #[Test]
    public function executeWillRunSuiteSequentially(): void
    {
        $orderedCommands = ['refactor', 'phpdoc', 'code-style', 'reports'];
        
        foreach ($orderedCommands as $commandName) {
            $prophecy = $this->prophesize(Command::class);
            $prophecy->ignoreValidationErrors()->shouldBeCalled();
            $prophecy->run(Argument::any(), Argument::any())->willReturn(StandardsCommand::SUCCESS);
            
            $this->application->find($commandName)->willReturn($prophecy->reveal());
        }

        self::assertSame(StandardsCommand::SUCCESS, $this->invokeExecute());
    }
}
