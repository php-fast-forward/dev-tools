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
use FastForward\DevTools\CodeOwners\CodeOwnersGenerator;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates and synchronizes CODEOWNERS files from local project metadata.
 */
#[AsCommand(
    name: 'github:codeowners',
    description: 'Generates .github/CODEOWNERS from local project metadata.',
    aliases: ['.github/CODEOWNERS', 'codeowners'],
)]
final class CodeOwnersCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new command instance.
     *
     * @param CodeOwnersGenerator $generator the generator used to infer and render CODEOWNERS contents
     * @param FilesystemInterface $filesystem the filesystem used to read and write the target file
     * @param FileDiffer $fileDiffer the differ used to report managed-file drift
     * @param LoggerInterface $logger the output-aware logger
     * @param SymfonyStyle $io the SymfonyStyle instance for interactive prompts
     */
    public function __construct(
        private readonly CodeOwnersGenerator $generator,
        private readonly FilesystemInterface $filesystem,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
        private readonly SymfonyStyle $io,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command infers CODEOWNERS entries from composer.json metadata, falls back to a commented'
            . ' template, and supports drift-aware preview and overwrite flows.'
        );

        $this->addJsonOption()
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
        $targetDirectory = $this->filesystem->getDirectory($targetPath);
        $overwrite = (bool) $input->getOption('overwrite');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        if (! $overwrite && ! $dryRun && ! $check && ! $interactive && $this->filesystem->exists($targetPath)) {
            return $this->success(
                'Managed file {target_path} already exists. Skipping CODEOWNERS generation.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                LogLevel::NOTICE,
            );
        }

        $owners = $this->generator->inferOwners();

        if ([] === $owners && $interactive && $input->isInteractive()) {
            $owners = $this->promptForOwners();
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

        $this->notice($comparison->getSummary(), $input, [
            'target_path' => $targetPath,
        ]);

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->notice(
                    $consoleDiff,
                    $input,
                    [
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
            $targetPath
        )) {
            return $this->success(
                'Skipped updating {target_path}.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                LogLevel::NOTICE,
            );
        }

        if (! $this->filesystem->exists($targetDirectory)) {
            $this->filesystem->mkdir($targetDirectory);
        }

        $this->filesystem->dumpFile($targetPath, $generatedContent);

        return $this->success('Updated CODEOWNERS in {target_path}.', $input, [
            'target_path' => $targetPath,
        ]);
    }

    /**
     * Prompts for CODEOWNERS entries when metadata inference is insufficient.
     *
     * @return list<string>
     */
    private function promptForOwners(): array
    {
        $answer = (string) $this->io->ask(
            'No CODEOWNERS entries could be inferred from composer.json. Enter space-separated owners for "*" or leave blank to use a commented placeholder: ',
        );

        return $this->generator->normalizeOwners($answer ?? '');
    }

    /**
     * Prompts whether the generated CODEOWNERS file should be written.
     *
     * @param string $targetPath the target file path
     *
     * @return bool true when the write SHOULD proceed
     */
    private function shouldWriteCodeOwners(string $targetPath): bool
    {
        $confirmation = new ConfirmationQuestion(
            \sprintf(
                'The generated CODEOWNERS file differs from the existing file at %s. Overwrite? [y/N] ',
                $targetPath
            ),
            false,
        );

        return $this->io->askQuestion($confirmation);
    }
}
