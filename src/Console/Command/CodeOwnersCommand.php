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
use FastForward\DevTools\CodeOwners\CodeOwnersGenerator;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Generates and synchronizes CODEOWNERS files from local project metadata.
 */
#[AsCommand(
    name: 'codeowners',
    description: 'Generates .github/CODEOWNERS from local project metadata.',
    help: 'This command infers CODEOWNERS entries from composer.json metadata, falls back to a commented template, and supports drift-aware preview and overwrite flows.'
)]
final class CodeOwnersCommand extends BaseCommand
{
    /**
     * Creates a new command instance.
     *
     * @param CodeOwnersGenerator $generator the generator used to infer and render CODEOWNERS contents
     * @param FilesystemInterface $filesystem the filesystem used to read and write the target file
     * @param FileDiffer $fileDiffer the differ used to report managed-file drift
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly CodeOwnersGenerator $generator,
        private readonly FilesystemInterface $filesystem,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the CODEOWNERS file to manage.',
                default: '.github/CODEOWNERS',
            )
            ->addOption(
                name: 'overwrite',
                shortcut: 'o',
                mode: InputOption::VALUE_NONE,
                description: 'Replace an existing CODEOWNERS file.',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview CODEOWNERS drift without writing the file.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report CODEOWNERS drift and exit non-zero when updates are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt for owners and confirmation before replacing CODEOWNERS.',
            )
            ->addOption(
                name: 'output-format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: 'text',
                suggestedValues: ['text', 'json'],
            );
    }

    /**
     * Generates or updates the CODEOWNERS file.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPath = $this->filesystem->getAbsolutePath((string) $input->getOption('file'));
        $targetDirectory = $this->filesystem->dirname($targetPath);
        $overwrite = (bool) $input->getOption('overwrite');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($targetPath)) {
            $this->logger->notice(
                'Managed file {target_path} already exists. Skipping CODEOWNERS generation.',
                [
                    'command' => 'codeowners',
                    'target_path' => $targetPath,
                ],
            );

            return self::SUCCESS;
        }

        $owners = $this->generator->inferOwners();

        if ([] === $owners && $interactive && $input->isInteractive()) {
            $owners = $this->promptForOwners($input, $output);
        }

        $generatedContent = $this->generator->generate($owners);
        $existingContent = $this->filesystem->exists($targetPath) ? $this->filesystem->readFile($targetPath) : null;

        $comparison = $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            $existingContent,
            null === $existingContent
                ? \sprintf('Managed file %s will be created from generated CODEOWNERS content.', $targetPath)
                : \sprintf('Updating managed file %s from generated CODEOWNERS content.', $targetPath),
        );

        $this->logger->notice(
            $comparison->getSummary(),
            [
                'command' => 'codeowners',
                'target_path' => $targetPath,
            ],
        );

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->logger->notice(
                    $consoleDiff,
                    [
                        'command' => 'codeowners',
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

        if (null !== $existingContent && $interactive && $input->isInteractive() && ! $this->shouldWriteCodeOwners(
            $input,
            $output,
            $targetPath
        )) {
            $this->logger->notice(
                'Skipped updating {target_path}.',
                [
                    'command' => 'codeowners',
                    'target_path' => $targetPath,
                ],
            );

            return self::SUCCESS;
        }

        if (! $this->filesystem->exists($targetDirectory)) {
            $this->filesystem->mkdir($targetDirectory);
        }

        $this->filesystem->dumpFile($targetPath, $generatedContent);
        $this->logger->info(
            'Updated CODEOWNERS in {target_path}.',
            [
                'command' => 'codeowners',
                'target_path' => $targetPath,
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Prompts for CODEOWNERS entries when metadata inference is insufficient.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return list<string>
     */
    private function promptForOwners(InputInterface $input, OutputInterface $output): array
    {
        $question = new Question(
            'No CODEOWNERS entries could be inferred from composer.json. Enter space-separated owners for "*" or leave blank to use a commented placeholder: ',
            '',
        );

        $answer = (string) $this->getHelper('question')
            ->ask($input, $output, $question);

        return $this->generator->normalizeOwners($answer);
    }

    /**
     * Prompts whether the generated CODEOWNERS file should be written.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $targetPath the target file path
     *
     * @return bool true when the write SHOULD proceed
     */
    private function shouldWriteCodeOwners(InputInterface $input, OutputInterface $output, string $targetPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Write managed file %s? [y/N] ', $targetPath), false);

        return (bool) $this->getHelper('question')
            ->ask($input, $output, $question);
    }
}
