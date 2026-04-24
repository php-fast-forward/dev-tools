<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Resource\FileDiff;
use Composer\IO\IOInterface;
use FastForward\DevTools\Console\Command\GitHooksCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

use function Safe\mkdir;
use function Safe\file_put_contents;
use function Safe\unlink;
use function Safe\rmdir;

#[CoversClass(GitHooksCommand::class)]
#[UsesClass(FileDiff::class)]
#[UsesTrait(LogsCommandResults::class)]
final class GitHooksCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $finderFactory;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileDiffer;

    private ObjectProphecy $logger;

    private ObjectProphecy $io;

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
        $this->finderFactory = $this->prophesize(FinderFactoryInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->io = $this->prophesize(IOInterface::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->log(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);

        $this->command = new GitHooksCommand(
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            $this->finderFactory->reveal(),
            $this->fileDiffer->reveal(),
            $this->logger->reveal(),
        );
        $this->command->setIO($this->io->reveal());
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
            'This command copies packaged Git hooks into the current repository.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCopyPackagedHooks(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(false);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(false);
        $this->filesystem->copy(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge', false)
            ->shouldBeCalledOnce();
        $this->filesystem->chmod('/app/.git/hooks/post-merge', 0o755)
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Installed {hook_name} hook.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Git hook synchronization completed successfully.', Argument::type('array'))
            ->shouldBeCalledOnce();

        self::assertSame(GitHooksCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipExistingHooksWhenNoOverwriteIsRequested(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(true);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(true);
        $this->logger->notice(
            'Skipped existing {hook_name} hook.',
            [
                'input' => $this->input->reveal(),
                'hook_name' => 'post-merge',
                'hook_path' => '/app/.git/hooks/post-merge',
            ],
        )
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Git hook synchronization completed successfully.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitHooksCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenHookWouldChange(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(true);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(true);
        $this->fileDiffer->diff(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge')
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Changed summary',
                "@@ -1 +1 @@\n-old\n+new",
            ))->shouldBeCalledOnce();
        $this->fileDiffer->formatForConsole("@@ -1 +1 @@\n-old\n+new", false)
            ->willReturn("@@ -1 +1 @@\n-old\n+new")
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Changed summary',
            [
                'input' => $this->input->reveal(),
                'hook_name' => 'post-merge',
                'hook_path' => '/app/.git/hooks/post-merge',
            ],
        )
            ->shouldBeCalledOnce();
        $this->logger->notice(
            "@@ -1 +1 @@\n-old\n+new",
            [
                'input' => $this->input->reveal(),
                'hook_name' => 'post-merge',
                'hook_path' => '/app/.git/hooks/post-merge',
                'diff' => "@@ -1 +1 @@\n-old\n+new",
            ],
        )
            ->shouldBeCalledOnce();
        $this->logger->error('One or more Git hooks require synchronization updates.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitHooksCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipReplacingHookWhenInteractiveConfirmationIsDeclined(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(true);
        $this->fileDiffer->diff(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge')
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Changed summary',
                "@@ -1 +1 @@\n-old\n+new",
            ))->shouldBeCalledOnce();
        $this->fileDiffer->formatForConsole("@@ -1 +1 @@\n-old\n+new", false)
            ->willReturn("@@ -1 +1 @@\n-old\n+new")
            ->shouldBeCalledOnce();
        $this->io->askConfirmation('Replace drifted Git hook /app/.git/hooks/post-merge? [y/N] ', false)
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Skipped replacing {hook_path}.',
            [
                'input' => $this->input->reveal(),
                'hook_name' => 'post-merge',
                'hook_path' => '/app/.git/hooks/post-merge',
            ],
        )
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Git hook synchronization completed successfully.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitHooksCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRemoveDriftedHookBeforeReplacingIt(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(false);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(true);
        $this->fileDiffer->diff(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge')
            ->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Changed summary'))
            ->shouldBeCalledOnce();
        $this->filesystem->remove('/app/.git/hooks/post-merge')
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge', false)
            ->shouldBeCalledOnce();
        $this->filesystem->chmod('/app/.git/hooks/post-merge', 0o755)
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Installed {hook_name} hook.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->logger->log('info', 'Git hook synchronization completed successfully.', Argument::type('array'))
            ->shouldBeCalledOnce();

        self::assertSame(GitHooksCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReportInstallFailureWhenReplacementStillCannotBeWritten(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/git-hooks');
        $this->input->getOption('target')
            ->willReturn('.git/hooks');
        $this->input->getOption('no-overwrite')
            ->willReturn(false);

        $this->fileLocator->locate('resources/git-hooks')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.git/hooks')
            ->willReturn('/app/.git/hooks');
        $this->filesystem->exists('/app/.git/hooks/post-merge')
            ->willReturn(true);
        $this->fileDiffer->diff(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge')
            ->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Changed summary'))
            ->shouldBeCalledOnce();
        $this->filesystem->remove('/app/.git/hooks/post-merge')
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::containingString('/post-merge'), '/app/.git/hooks/post-merge', false)
            ->willThrow(
                new IOException('Target file could not be opened for writing.', 0, null, '/app/.git/hooks/post-merge')
            )
            ->shouldBeCalledOnce();
        $this->filesystem->basename('/app/.git/hooks/post-merge')
            ->willReturn('post-merge')
            ->shouldBeCalledOnce();
        $this->logger->error(
            'Failed to install {hook_name} hook automatically. Remove or unlock {hook_path} and rerun git-hooks.',
            Argument::that(
                static fn(array $context): bool => $context['input'] instanceof InputInterface
                    && 'post-merge' === $context['hook_name']
                    && '/app/.git/hooks/post-merge' === $context['hook_path']
                    && '/app/.git/hooks/post-merge' === $context['file']
                    && null === $context['line']
                    && str_contains((string) $context['error'], 'Target file could not be opened for writing.')
            ),
        )->shouldBeCalledOnce();
        $this->logger->error('One or more Git hooks could not be installed automatically.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->chmod(Argument::cetera())
            ->shouldNotBeCalled();

        self::assertSame(GitHooksCommand::FAILURE, $this->executeCommand());
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
