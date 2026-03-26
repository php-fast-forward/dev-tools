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

use FastForward\DevTools\Command\ReportsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;

#[CoversClass(ReportsCommand::class)]
final class ReportsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return ReportsCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'reports';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Generates the frontpage for Fast Forward documentation.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunDocsAndTestsCommand(): void
    {
        $docsCommand = $this->prophesize(Command::class);
        $docsCommand->ignoreValidationErrors()
            ->shouldBeCalled();
        $docsCommand->run(Argument::any(), Argument::any())->willReturn(ReportsCommand::SUCCESS);

        $testsCommand = $this->prophesize(Command::class);
        $testsCommand->ignoreValidationErrors()
            ->shouldBeCalled();
        $testsCommand->run(Argument::any(), Argument::any())->willReturn(ReportsCommand::SUCCESS);

        $this->application->find('docs')
            ->willReturn($docsCommand->reveal());
        $this->application->find('tests')
            ->willReturn($testsCommand->reveal());

        self::assertSame(ReportsCommand::SUCCESS, $this->invokeExecute());
    }
}
