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
use Composer\Command\BaseCommand;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Copies packaged or local resources into the consumer repository.
 */
#[AsCommand(name: 'copy-resource', description: 'Copies a file or directory resource into the current project.')]
final class CopyResourceCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new CopyResourceCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used for copy operations
     * @param FileLocatorInterface $fileLocator the locator used to resolve source resources
     * @param FinderFactoryInterface $finderFactory the factory used to create finders for directory resources
     * @param FileDiffer $fileDiffer the service used to summarize overwrite changes
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FinderFactoryInterface $finderFactory,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures source, target, and overwrite controls.
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command copies a configured source file or every file in a source directory into the target'
            . ' path.'
        );

        $this->addJsonOption()
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
            return $this->failure('The --source and --target options are required.', $input);
        }

        $sourcePath = $this->fileLocator->locate($source);
        $targetPath = (string) $this->filesystem->getAbsolutePath($target);

        if (is_dir($sourcePath)) {
            return $this->copyDirectory(
                $sourcePath,
                $targetPath,
                $overwrite,
                $dryRun,
                $check,
                $interactive,
                $input,
                $output
            );
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
     * @param bool $dryRun
     * @param bool $check
     * @param bool $interactive
     * @param InputInterface $input
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
                $this->copyFile(
                    $file->getRealPath(),
                    $destination,
                    $overwrite,
                    $dryRun,
                    $check,
                    $interactive,
                    $input,
                    $output
                ),
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
     * @param bool $dryRun
     * @param bool $check
     * @param bool $interactive
     * @param InputInterface $input
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
    ): int {
        if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($targetPath)) {
            return $this->success(
                'Skipped existing resource {target_path}.',
                $input,
                [
                    'source_path' => $sourcePath,
                    'target_path' => $targetPath,
                ],
                LogLevel::NOTICE,
            );
        }

        if (($overwrite || $dryRun || $check || $interactive) && $this->filesystem->exists($targetPath)) {
            $comparison = $this->fileDiffer->diff($sourcePath, $targetPath);

            $this->logger->notice(
                $comparison->getSummary(),
                [
                    'input' => $input,
                    'source_path' => $sourcePath,
                    'target_path' => $targetPath,
                ],
            );

            if ($comparison->isChanged()) {
                $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

                if (null !== $consoleDiff) {
                    $this->notice(
                        $consoleDiff,
                        $input,
                        [
                            'source_path' => $sourcePath,
                            'target_path' => $targetPath,
                            'diff' => $comparison->getDiff(),
                        ],
                    );
                }
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

            if ($interactive && $input->isInteractive() && ! $this->shouldReplaceResource($targetPath)) {
                return $this->success(
                    'Skipped replacing {target_path}.',
                    $input,
                    [
                        'source_path' => $sourcePath,
                        'target_path' => $targetPath,
                    ],
                    LogLevel::NOTICE,
                );
            }
        }

        $this->filesystem->copy($sourcePath, $targetPath, $overwrite || $interactive);

        return $this->success(
            'Copied resource {target_path}.',
            $input,
            [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
            ],
        );
    }

    /**
     * Prompts whether a drifted resource should be replaced.
     *
     * @param string $targetPath the resource path that would be replaced
     *
     * @return bool true when the replacement SHOULD proceed
     */
    private function shouldReplaceResource(string $targetPath): bool
    {
        return $this->getIO()
            ->askConfirmation(\sprintf('Replace drifted resource %s? [y/N] ', $targetPath), false);
    }
}
