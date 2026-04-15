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
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Config\FileLocatorInterface;

#[CoversClass(CodeStyleCommand::class)]
#[CoversClass(ProcessBuilder::class)]
#[CoversClass(ProcessQueue::class)]
final class CodeStyleCommandTest extends TestCase
{
    use ProphecyTrait;

    private CodeStyleCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $fileLocator->method('locate')
            ->willReturn('/path/to/ecs.php');

        $processBuilder = new ProcessBuilder();
        $processQueue = new ProcessQueue();

        $this->command = new CodeStyleCommand($fileLocator, $processBuilder, $processQueue);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('code-style', $this->command->getName());
        self::assertSame(
            'Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandCanBeConstructedWithDependencies(): void
    {
        self::assertInstanceOf(CodeStyleCommand::class, $this->command);
    }
}
