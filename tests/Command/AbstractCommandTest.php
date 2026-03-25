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

use FastForward\DevTools\Command\AbstractCommand;
use FastForward\DevTools\Tests\Command\AbstractCommandTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function Safe\getcwd;

#[CoversClass(AbstractCommand::class)]
final class AbstractCommandTest extends AbstractCommandTestCase
{
    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return AbstractCommandStub::class;
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
        $this->filesystem->isAbsolutePath('/absolute/path')->willReturn(true);

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
    protected function configure(): void
    {
        $this->setName('stub')
            ->setDescription('Stub command for testing AbstractCommand.')
            ->setHelp('This is a stub command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    public function publicGetAbsolutePath(string $path): string
    {
        return $this->getAbsolutePath($path);
    }

    public function publicGetProjectName(): string
    {
        return $this->getProjectName();
    }
}
