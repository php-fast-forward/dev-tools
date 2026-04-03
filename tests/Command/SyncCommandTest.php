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

use FastForward\DevTools\Command\SyncCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(SyncCommand::class)]
final class SyncCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return SyncCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'dev-tools:sync';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, and .editorconfig in the root project.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command adds or updates dev-tools scripts in composer.json, copies reusable GitHub Actions workflows, and ensures .editorconfig is present and up to date.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteInfo(): void
    {
        // Filesystem mocks para não executar nada real
        $this->filesystem->exists(Argument::any())->willReturn(true);
        $this->filesystem->dumpFile(Argument::cetera())->shouldBeCalled();

        // Output deve receber a mensagem inicial
        $this->output->writeln('<info>Starting script installation...</info>')
            ->shouldBeCalled();

        $result = $this->invokeExecute();
        self::assertSame(0, $result);
    }
}
