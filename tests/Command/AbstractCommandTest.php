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

use FastForward\DevTools\Console\Command\AbstractCommand;
use FastForward\DevTools\Console\Command\AbstractCommand as AbstractCommandBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(AbstractCommand::class)]
final class AbstractCommandTest extends AbstractCommandTestCase
{
    /**
     * @return AbstractCommand
     */
    protected function getCommandClass(): AbstractCommand
    {
        return new AbstractCommandStub($this->filesystem->reveal());
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'stub';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Stub command for testing AbstractCommand.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This is a stub command.';
    }

    /**
     * @return void
     */
    #[Test]
    public function getAbsolutePathWillReturnAbsolutePathIfProvided(): void
    {
        $this->filesystem->isAbsolutePath('/absolute/path')
            ->willReturn(true);

        self::assertSame('/absolute/path', $this->command->publicGetAbsolutePath('/absolute/path'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getProjectNameWillReturnPackageNameFromComposer(): void
    {
        self::assertSame('fast-forward/dev-tools', $this->command->publicGetProjectName());
    }
}

/**
 * Stub class to test protected methods and base logic of AbstractCommand.
 */
class AbstractCommandStub extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('stub')
            ->setDescription('Stub command for testing AbstractCommand.')
            ->setHelp('This is a stub command.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function publicGetAbsolutePath(string $path): string
    {
        return $this->getAbsolutePath($path);
    }

    /**
     * @return string
     */
    public function publicGetProjectName(): string
    {
        return $this->getProjectName();
    }
}
