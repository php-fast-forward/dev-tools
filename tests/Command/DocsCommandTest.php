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

use FastForward\DevTools\Command\DocsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(DocsCommand::class)]
final class DocsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @return string
     */
    protected function getCommandClass(): string
    {
        return DocsCommand::class;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'docs';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Generates API documentation.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command generates API documentation using phpDocumentor.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailIfSourceDirectoryNotFound(): void
    {
        $this->filesystem->exists(Argument::any())->willReturn(false);

        self::assertSame(DocsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillGeneratePhpDocumentorConfigAndRunProcess(): void
    {
        $this->filesystem->exists(Argument::any())->willReturn(true);
        // O template agora é resolvido via getConfigFile, então precisamos garantir que o mock aceite o caminho relativo
        $this->filesystem->readFile(Argument::that(
            // Aceita tanto caminho absoluto quanto relativo, pois getConfigFile pode resolver ambos
            fn($file): bool => str_contains((string) $file, 'resources/phpdocumentor.xml')
        ))->willReturn('template_content');
        $this->filesystem->dumpFile(Argument::cetera())->shouldBeCalled();

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpdoc')
                && str_contains($commandLine, '--config');
        });

        self::assertSame(DocsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->filesystem->exists(Argument::any())->willReturn(true);
        $this->filesystem->readFile(Argument::cetera())->willReturn('template_content');
        $this->filesystem->dumpFile(Argument::cetera())->shouldBeCalled();

        $this->willRunProcessWithCallback(static fn(): bool => true, false);

        self::assertSame(DocsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateConfigDirectoryIfItDoesNotExist(): void
    {
        $this->filesystem->exists(Argument::any())->willReturn(true);
        $this->filesystem->exists(getcwd() . '/tmp/cache/phpdoc')->willReturn(false);

        $this->filesystem->readFile(Argument::cetera())->willReturn('template_content');
        $this->filesystem->mkdir(Argument::any())->shouldBeCalled();
        $this->filesystem->dumpFile(Argument::cetera())->shouldBeCalled();

        $this->willRunProcessWithCallback(static fn(): bool => true);

        self::assertSame(DocsCommand::SUCCESS, $this->invokeExecute());
    }
}
