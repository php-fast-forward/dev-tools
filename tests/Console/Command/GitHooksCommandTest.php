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

use FastForward\DevTools\Console\Command\GitHooksCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

#[CoversClass(GitHooksCommand::class)]
#[UsesClass(ProcessBuilder::class)]
final class GitHooksCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private GitHooksCommand $command;

    private string $sourceDirectory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sourceDirectory = sys_get_temp_dir() . '/git-hooks-command-test-' . bin2hex(random_bytes(4));
        mkdir($this->sourceDirectory, 0o777, true);
        file_put_contents($this->sourceDirectory . '/post-merge', '#!/bin/sh');

        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new GitHooksCommand(
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            new Finder(),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_dir($this->sourceDirectory)) {
            unlink($this->sourceDirectory . '/post-merge');
            rmdir($this->sourceDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('git-hooks', $this->command->getName());
        self::assertSame('Installs Fast Forward Git hooks.', $this->command->getDescription());
        self::assertSame(
            'This command runs GrumPHP hook initialization and copies packaged Git hooks into the current repository.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunGrumPhpInitAndCopyHooks(): void
    {
        $this->input->getOption('skip-grumphp-init')->willReturn(false);
        $this->input->getOption('source')->willReturn('resources/git-hooks');
        $this->input->getOption('target')->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')->willReturn(false);

        $this->processQueue->add(Argument::type(Process::class))
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(GitHooksCommand::SUCCESS)
            ->shouldBeCalledOnce();

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->copy(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge', true)
            ->shouldBeCalledOnce();
        $this->filesystem->chmod('/app/.git/hooks/post-merge', 0o755, 0o755)
            ->shouldBeCalledOnce();

        self::assertSame(GitHooksCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
