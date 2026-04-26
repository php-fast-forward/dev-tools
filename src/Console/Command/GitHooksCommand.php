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

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Installs packaged Git hooks for the consumer repository.
 */
#[AsCommand(
    name: 'git:hooks',
    description: 'Installs Fast Forward Git hooks.',
    aliases: ['.git/hooks', 'git-hooks'],
)]
final class GitHooksCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new GitHooksCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used to copy hooks
     * @param FileLocatorInterface $fileLocator the locator used to find packaged hooks
     * @param FinderFactoryInterface $finderFactory the factory used to create finders for hook files
     * @param FileDiffer $fileDiffer
     * @param LoggerInterface $logger the output-aware logger
     * @param SymfonyStyle $io
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FinderFactoryInterface $finderFactory,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
        private readonly SymfonyStyle $io,
    ) {
        parent::__construct();
    }

    /**
     * Configures hook source, target, and initialization options.
     */
    protected function configure(): void
    {
        $this->setHelp('This command copies packaged Git hooks into the current repository.');

        $this->addJsonOption()
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the packaged Git hooks directory.',
                default: 'resources/git-hooks',
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the target Git hooks directory.',
                default: '.git/hooks',
            )
            ->addOption(
                name: 'no-overwrite',
                mode: InputOption::VALUE_NONE,
                description: 'Do not overwrite existing hook files.',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview Git hook synchronization without copying files.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report Git hook drift and exit non-zero when replacements are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before replacing drifted Git hooks.',
            );
    }

    /**
     * Copies packaged Git hooks.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourcePath = $this->fileLocator->locate((string) $input->getOption('source'));
        $targetPath = (string) $this->filesystem->getAbsolutePath((string) $input->getOption('target'));
        $overwrite = ! $input->getOption('no-overwrite');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        $files = $this->finderFactory
            ->create()
            ->files()
            ->in($sourcePath);

        $checkFailure = false;
        $installFailure = false;

        foreach ($files as $file) {
            $hookPath = Path::join($targetPath, $file->getRelativePathname());

            if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($hookPath)) {
                $this->notice(
                    'Skipped existing {hook_name} hook.',
                    $input,
                    [
                        'hook_name' => $file->getFilename(),
                        'hook_path' => $hookPath,
                    ],
                );

                continue;
            }

            if (($overwrite || $dryRun || $check || $interactive) && $this->filesystem->exists($hookPath)) {
                $comparison = $this->fileDiffer->diff($file->getRealPath(), $hookPath);

                $this->logger->notice(
                    $comparison->getSummary(),
                    [
                        'input' => $input,
                        'hook_name' => $file->getFilename(),
                        'hook_path' => $hookPath,
                    ],
                );

                if ($comparison->isChanged()) {
                    $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

                    if (null !== $consoleDiff) {
                        $this->logger->notice(
                            $consoleDiff,
                            [
                                'input' => $input,
                                'hook_name' => $file->getFilename(),
                                'hook_path' => $hookPath,
                                'diff' => $comparison->getDiff(),
                            ],
                        );
                    }
                }

                if ($comparison->isUnchanged()) {
                    continue;
                }

                if ($check) {
                    $checkFailure = true;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                if ($interactive && $input->isInteractive() && ! $this->shouldReplaceHook($hookPath)) {
                    $this->notice(
                        'Skipped replacing {hook_path}.',
                        $input,
                        [
                            'hook_name' => $file->getFilename(),
                            'hook_path' => $hookPath,
                        ],
                    );

                    continue;
                }
            }

            if (! $this->installHook($file->getRealPath(), $hookPath, $overwrite || $interactive, $input)) {
                $installFailure = true;

                continue;
            }

            $this->success(
                'Installed {hook_name} hook.',
                $input,
                [
                    'hook_name' => $file->getFilename(),
                    'hook_path' => $hookPath,
                ],
            );
        }

        if ($checkFailure) {
            return $this->failure(
                'One or more Git hooks require synchronization updates.',
                $input,
                [
                    'target' => $targetPath,
                ],
                $targetPath,
            );
        }

        if ($installFailure) {
            return $this->failure(
                'One or more Git hooks could not be installed automatically.',
                $input,
                [
                    'target' => $targetPath,
                ],
                $targetPath,
            );
        }

        return $this->success(
            'Git hook synchronization completed successfully.',
            $input,
            [
                'target' => $targetPath,
            ],
        );
    }

    /**
     * Prompts whether a drifted hook should be replaced.
     *
     * @param string $hookPath the hook path that would be replaced
     *
     * @return bool true when the replacement SHOULD proceed
     */
    private function shouldReplaceHook(string $hookPath): bool
    {
        $confirmation = new ConfirmationQuestion(
            \sprintf('Replace drifted Git hook %s? [y/N] ', $hookPath),
            false,
        );

        return $this->io->askQuestion($confirmation);
    }

    /**
     * Installs a single hook and rewrites drifted targets defensively.
     *
     * @param string $sourcePath the packaged hook path
     * @param string $hookPath the target repository hook path
     * @param bool $replaceExisting whether an existing hook SHOULD be removed first
     * @param InputInterface $input the originating command input
     *
     * @return bool true when the hook was installed successfully
     */
    private function installHook(
        string $sourcePath,
        string $hookPath,
        bool $replaceExisting,
        InputInterface $input
    ): bool {
        try {
            if ($replaceExisting && $this->filesystem->exists($hookPath)) {
                $this->filesystem->remove($hookPath);
            }

            $this->filesystem->copy($sourcePath, $hookPath, false);
            $this->filesystem->chmod(files: $hookPath, mode: 0o755);

            return true;
        } catch (IOExceptionInterface $ioException) {
            $this->logger->error(
                'Failed to install {hook_name} hook automatically. Remove or unlock {hook_path} and rerun git-hooks.',
                [
                    'input' => $input,
                    'hook_name' => $this->filesystem->getBasename($hookPath),
                    'hook_path' => $hookPath,
                    'error' => $ioException->getMessage(),
                    'file' => $ioException->getPath() ?? $hookPath,
                    'line' => null,
                ],
            );

            return false;
        }
    }
}
