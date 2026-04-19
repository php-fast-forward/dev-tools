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
 * Copies packaged or local resources into the consumer repository.
 */
#[AsCommand(
    name: 'copy-resource',
    description: 'Copies a file or directory resource into the current project.',
    help: 'This command copies a configured source file or every file in a source directory into the target path.'
)]
final class CopyResourceCommand extends BaseCommand
{
    /**
     * Creates a new CopyResourceCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used for copy operations
     * @param FileLocatorInterface $fileLocator the locator used to resolve source resources
     * @param FinderFactoryInterface $finderFactory the factory used to create finders for directory resources
     * @param OverwriteDiffRenderer $overwriteDiffRenderer the renderer used to summarize overwrite changes
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
     * Configures source, target, and overwrite controls.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Source file or directory to copy.',
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Target file or directory path.',
            )
            ->addOption(
                name: 'overwrite',
                shortcut: 'o',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite existing target files.',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview copied resources without writing files.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report copied-resource drift and exit non-zero when changes are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before replacing drifted resources.',
            );
    }

    /**
     * Copies the requested resource.
     *
     * @param InputInterface $input the input containing source and target paths
     * @param OutputInterface $output the output used to report copy results
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string) $input->getOption('source');
        $target = (string) $input->getOption('target');
        $overwrite = (bool) $input->getOption('overwrite');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        if ('' === $source || '' === $target) {
            $output->writeln('<error>The --source and --target options are required.</error>');

            return self::FAILURE;
        }

        $sourcePath = $this->fileLocator->locate($source);
        $targetPath = (string) $this->filesystem->getAbsolutePath($target);

        if (is_dir($sourcePath)) {
            return $this->copyDirectory($sourcePath, $targetPath, $overwrite, $dryRun, $check, $interactive, $input, $output);
        }

        return $this->copyFile($sourcePath, $targetPath, $overwrite, $dryRun, $check, $interactive, $input, $output);
    }

    /**
     * Copies every file from a source directory into the target directory.
     *
     * @param string $sourcePath the resolved source directory
     * @param string $targetPath the resolved target directory
     * @param bool $overwrite whether existing files MAY be overwritten
     * @param OutputInterface $output the output used to report copy results
     *
     * @return int the command status code
     */
    private function copyDirectory(
        string $sourcePath,
        string $targetPath,
        bool $overwrite,
        bool $dryRun,
        bool $check,
        bool $interactive,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $files = $this->finderFactory
            ->create()
            ->files()
            ->in($sourcePath);

        $status = self::SUCCESS;

        foreach ($files as $file) {
            $destination = Path::join($targetPath, $file->getRelativePathname());
            $status = max(
                $status,
                $this->copyFile($file->getRealPath(), $destination, $overwrite, $dryRun, $check, $interactive, $input, $output),
            );
        }

        return $status;
    }

    /**
     * Copies a single file when the target does not exist or overwrite is enabled.
     *
     * @param string $sourcePath the resolved source file
     * @param string $targetPath the resolved target file
     * @param bool $overwrite whether an existing target file MAY be overwritten
     * @param OutputInterface $output the output used to report copy results
     *
     * @return int the command status code
     */
    private function copyFile(
        string $sourcePath,
        string $targetPath,
        bool $overwrite,
        bool $dryRun,
        bool $check,
        bool $interactive,
        InputInterface $input,
        OutputInterface $output,
    ): int
    {
        if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($targetPath)) {
            $output->writeln(\sprintf('<comment>Skipped existing resource %s.</comment>', $targetPath));

            return self::SUCCESS;
        }

        if (($overwrite || $dryRun || $check || $interactive) && $this->filesystem->exists($targetPath)) {
            $comparison = $this->overwriteDiffRenderer->render($sourcePath, $targetPath);

            $output->writeln(\sprintf('<comment>%s</comment>', $comparison->summary()));

            if ($comparison->isChanged() && null !== $comparison->diff()) {
                $output->writeln($comparison->diff());
            }

            if ($comparison->isUnchanged()) {
                return self::SUCCESS;
            }

            if ($check) {
                return self::FAILURE;
            }

            if ($dryRun) {
                return self::SUCCESS;
            }

            if ($interactive && $input->isInteractive() && ! $this->shouldReplaceResource($input, $output, $targetPath)) {
                $output->writeln(\sprintf('<comment>Skipped replacing %s.</comment>', $targetPath));

                return self::SUCCESS;
            }
        }

        $this->filesystem->copy($sourcePath, $targetPath, $overwrite || $interactive);
        $output->writeln(\sprintf('<info>Copied resource %s.</info>', $targetPath));

        return self::SUCCESS;
    }

    /**
     * Prompts whether a drifted resource should be replaced.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $targetPath the resource path that would be replaced
     *
     * @return bool true when the replacement SHOULD proceed
     */
    private function shouldReplaceResource(InputInterface $input, OutputInterface $output, string $targetPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Replace drifted resource %s? [y/N] ', $targetPath), false);

        return (bool) $this->getHelper('question')->ask($input, $output, $question);
    }
}
