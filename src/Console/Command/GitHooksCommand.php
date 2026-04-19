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

use Composer\Command\BaseCommand;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\OverwriteDiffRenderer;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Path;

/**
 * Installs packaged Git hooks for the consumer repository.
 */
#[AsCommand(
    name: 'git-hooks',
    description: 'Installs Fast Forward Git hooks.',
    help: 'This command copies packaged Git hooks into the current repository.'
)]
final class GitHooksCommand extends BaseCommand
{
    /**
     * Creates a new GitHooksCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used to copy hooks
     * @param FileLocatorInterface $fileLocator the locator used to find packaged hooks
     * @param FinderFactoryInterface $finderFactory the factory used to create finders for hook files
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FinderFactoryInterface $finderFactory,
        private readonly OverwriteDiffRenderer $overwriteDiffRenderer,
    ) {
        parent::__construct();
    }

    /**
     * Configures hook source, target, and initialization options.
     */
    protected function configure(): void
    {
        $this
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

        $status = self::SUCCESS;

        foreach ($files as $file) {
            $hookPath = Path::join($targetPath, $file->getRelativePathname());

            if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($hookPath)) {
                $output->writeln(\sprintf('<comment>Skipped existing %s hook.</comment>', $file->getFilename()));

                continue;
            }

            if (($overwrite || $dryRun || $check || $interactive) && $this->filesystem->exists($hookPath)) {
                $comparison = $this->overwriteDiffRenderer->render($file->getRealPath(), $hookPath);

                $output->writeln(\sprintf('<comment>%s</comment>', $comparison->summary()));

                if ($comparison->isChanged() && null !== $comparison->diff()) {
                    $output->writeln($comparison->diff());
                }

                if ($comparison->isUnchanged()) {
                    continue;
                }

                if ($check) {
                    $status = self::FAILURE;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                if ($interactive && $input->isInteractive() && ! $this->shouldReplaceHook($input, $output, $hookPath)) {
                    $output->writeln(\sprintf('<comment>Skipped replacing %s.</comment>', $hookPath));

                    continue;
                }
            }

            $this->filesystem->copy($file->getRealPath(), $hookPath, $overwrite || $interactive);
            $this->filesystem->chmod($hookPath, 755, 0o755);

            $output->writeln(\sprintf('<info>Installed %s hook.</info>', $file->getFilename()));
        }

        return $status;
    }

    /**
     * Prompts whether a drifted hook should be replaced.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $hookPath the hook path that would be replaced
     *
     * @return bool true when the replacement SHOULD proceed
     */
    private function shouldReplaceHook(InputInterface $input, OutputInterface $output, string $hookPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Replace drifted Git hook %s? [y/N] ', $hookPath), false);

        return (bool) $this->getHelper('question')->ask($input, $output, $question);
    }
}
