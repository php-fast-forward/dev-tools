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

use FastForward\DevTools\Command\PhpDocCommand;
use FastForward\DevTools\Command\RefactorCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(PhpDocCommand::class)]
final class PhpDocCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return PhpDocCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'phpdoc';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Checks and fixes PHPDocs.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command checks and fixes PHPDocs in your PHP files.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigFile(PhpDocCommand::CONFIG);
        $this->withConfigFile(RefactorCommand::CONFIG);

        $this->withConfigFile(PhpDocCommand::FILENAME);
        $this->withConfigFile(PhpDocCommand::FILENAME, true);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->willRunProcessWithCallback(static fn(): true => true, false);

        self::assertSame(PhpDocCommand::FAILURE, $this->invokeExecute());
    }
}

// VDI
