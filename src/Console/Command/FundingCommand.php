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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Funding\ComposerFundingCodec;
use FastForward\DevTools\Funding\FundingProfileMerger;
use FastForward\DevTools\Funding\FundingYamlCodec;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Synchronizes funding metadata between composer.json and .github/FUNDING.yml.
 */
#[AsCommand(
    name: 'github:funding',
    description: 'Synchronizes funding metadata between composer.json and .github/FUNDING.yml.',
    aliases: ['.github/FUNDING.yml', 'composer:funding', 'funding'],
)]
final class FundingCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new FundingCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used to read and write funding metadata files
     * @param ComposerFundingCodec $composerFundingCodec the codec used to parse and render Composer funding metadata
     * @param FundingYamlCodec $fundingYamlCodec the codec used to parse and render GitHub funding YAML metadata
     * @param FundingProfileMerger $fundingProfileMerger the merger used to synchronize normalized funding profiles
     * @param FileDiffer $fileDiffer the differ used to summarize managed-file drift
     * @param ProcessBuilderInterface $processBuilder the process builder used to normalize composer.json after updates
     * @param ProcessQueueInterface $processQueue the process queue used to execute composer normalize
     * @param LoggerInterface $logger the output-aware logger
     * @param SymfonyStyle $io
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ComposerFundingCodec $composerFundingCodec,
        private readonly FundingYamlCodec $fundingYamlCodec,
        private readonly FundingProfileMerger $fundingProfileMerger,
        private readonly FileDiffer $fileDiffer,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly LoggerInterface $logger,
        private readonly SymfonyStyle $io,
    ) {
        parent::__construct();
    }

    /**
     * Configures command options.
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command merges supported funding entries across composer.json and .github/FUNDING.yml while'
            . ' preserving unsupported providers.'
        );

        $this->addJsonOption()
            ->addOption(
                name: 'composer-file',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the composer.json file to synchronize.',
                default: 'composer.json',
            )
            ->addOption(
                name: 'funding-file',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the .github/FUNDING.yml file to synchronize.',
                default: '.github/FUNDING.yml',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview funding metadata synchronization without writing files.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report funding metadata drift and exit non-zero when updates are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before applying funding metadata updates.',
            );
    }

    /**
     * Synchronizes funding metadata across Composer and GitHub files.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composerFile = (string) $input->getOption('composer-file');
        $fundingFile = (string) $input->getOption('funding-file');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        $this->logger->info('Synchronizing funding metadata...', [
            'input' => $input,
        ]);

        if (! $this->filesystem->exists($composerFile)) {
            $this->notice(
                'Composer file {composer_file} does not exist. Skipping funding synchronization.',
                $input,
                [
                    'composer_file' => $composerFile,
                    'funding_file' => $fundingFile,
                ],
            );

            return $this->success(
                'Funding synchronization was skipped because composer.json was not found.',
                $input,
                [
                    'composer_file' => $composerFile,
                    'funding_file' => $fundingFile,
                ],
                'notice',
            );
        }

        $composerContents = $this->filesystem->readFile($composerFile);
        $fundingContents = $this->filesystem->exists($fundingFile) ? $this->filesystem->readFile($fundingFile) : null;

        $mergedProfile = $this->fundingProfileMerger->merge(
            $this->composerFundingCodec->parse($composerContents),
            $this->fundingYamlCodec->parse($fundingContents),
        );

        $updatedComposerContents = $this->composerFundingCodec->dump($composerContents, $mergedProfile);
        $updatedFundingContents = $mergedProfile->hasYamlContent()
            ? $this->fundingYamlCodec->dump($mergedProfile)
            : null;

        $composerStatus = $this->handleComposerFile(
            $composerFile,
            $composerContents,
            $updatedComposerContents,
            $dryRun,
            $check,
            $interactive,
            $input,
            $output,
        );

        $fundingStatus = $this->handleFundingFile(
            $fundingFile,
            $fundingContents,
            $updatedFundingContents,
            $dryRun,
            $check,
            $interactive,
            $input,
            $output,
        );

        return max($composerStatus, $fundingStatus);
    }

    /**
     * Handles composer.json synchronization reporting and writes.
     *
     * @param string $composerFile
     * @param string $composerContents
     * @param string $updatedComposerContents
     * @param bool $dryRun
     * @param bool $check
     * @param bool $interactive
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int the command status code
     */
    private function handleComposerFile(
        string $composerFile,
        string $composerContents,
        string $updatedComposerContents,
        bool $dryRun,
        bool $check,
        bool $interactive,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $comparison = $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            $composerFile,
            $updatedComposerContents,
            $composerContents,
            \sprintf('Updating managed file %s from generated funding metadata synchronization.', $composerFile),
        );

        $this->logger->notice(
            $comparison->getSummary(),
            [
                'input' => $input,
                'composer_file' => $composerFile,
            ],
        );

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->logger->notice(
                    $consoleDiff,
                    [
                        'input' => $input,
                        'composer_file' => $composerFile,
                        'diff' => $comparison->getDiff(),
                    ],
                );
            }
        }

        if ($comparison->isUnchanged()) {
            return $this->success(
                '{composer_file} already matches the synchronized funding metadata.',
                $input,
                [
                    'composer_file' => $composerFile,
                ],
            );
        }

        if ($check) {
            return $this->failure(
                '{composer_file} requires synchronized funding metadata updates.',
                $input,
                [
                    'composer_file' => $composerFile,
                ],
                $composerFile,
            );
        }

        if ($dryRun) {
            return $this->success(
                'Funding synchronization preview completed for {composer_file}.',
                $input,
                [
                    'composer_file' => $composerFile,
                ],
                'notice',
            );
        }

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteManagedFile($composerFile)) {
            $this->notice('Skipped updating {composer_file}.', $input, [
                'composer_file' => $composerFile,
            ]);

            return $this->success(
                'Funding synchronization was skipped for {composer_file}.',
                $input,
                [
                    'composer_file' => $composerFile,
                ],
                'notice',
            );
        }

        $this->filesystem->dumpFile($composerFile, $updatedComposerContents);

        if (self::SUCCESS !== $this->normalizeComposerFile($composerFile, $output)) {
            return $this->failure(
                'Composer normalization failed after updating {composer_file}.',
                $input,
                [
                    'composer_file' => $composerFile,
                ],
                $composerFile,
            );
        }

        return $this->success(
            'Updated funding metadata in {composer_file}.',
            $input,
            [
                'composer_file' => $composerFile,
            ],
        );
    }

    /**
     * Handles .github/FUNDING.yml synchronization reporting and writes.
     *
     * @param string $fundingFile
     * @param ?string $currentFundingContents
     * @param ?string $updatedFundingContents
     * @param bool $dryRun
     * @param bool $check
     * @param bool $interactive
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int the command status code
     */
    private function handleFundingFile(
        string $fundingFile,
        ?string $currentFundingContents,
        ?string $updatedFundingContents,
        bool $dryRun,
        bool $check,
        bool $interactive,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        if (null === $updatedFundingContents && null === $currentFundingContents) {
            $this->notice(
                'No supported funding metadata found. Skipping .github/FUNDING.yml synchronization.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
            );

            return $this->success(
                'Funding synchronization found no supported GitHub funding metadata to write.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
                'notice',
            );
        }

        if (null === $updatedFundingContents) {
            return $this->success(
                'No GitHub funding file changes were required.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
            );
        }

        $comparison = $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            $fundingFile,
            $updatedFundingContents,
            $currentFundingContents,
            null === $currentFundingContents
                ? \sprintf(
                    'Managed file %s will be created from generated funding metadata synchronization.',
                    $fundingFile
                )
                : \sprintf('Updating managed file %s from generated funding metadata synchronization.', $fundingFile),
        );

        $this->logger->notice($comparison->getSummary(), [
            'input' => $input,
            'funding_file' => $fundingFile,
        ]);

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->logger->notice(
                    $consoleDiff,
                    [
                        'input' => $input,
                        'funding_file' => $fundingFile,
                        'diff' => $comparison->getDiff(),
                    ],
                );
            }
        }

        if ($comparison->isUnchanged()) {
            return $this->success(
                '{funding_file} already matches the synchronized funding metadata.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
            );
        }

        if ($check) {
            return $this->failure(
                '{funding_file} requires synchronized funding metadata updates.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
                $fundingFile,
            );
        }

        if ($dryRun) {
            return $this->success(
                'Funding synchronization preview completed for {funding_file}.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
                'notice',
            );
        }

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteManagedFile($fundingFile)) {
            $this->notice('Skipped updating {funding_file}.', $input, [
                'funding_file' => $fundingFile,
            ]);

            return $this->success(
                'Funding synchronization was skipped for {funding_file}.',
                $input,
                [
                    'funding_file' => $fundingFile,
                ],
                'notice',
            );
        }

        $this->filesystem->mkdir($this->filesystem->getDirectory($fundingFile));
        $this->filesystem->dumpFile($fundingFile, $updatedFundingContents);

        return $this->success(
            'Updated funding metadata in {funding_file}.',
            $input,
            [
                'funding_file' => $fundingFile,
            ],
        );
    }

    /**
     * Prompts whether a managed file should be written.
     *
     * @param string $targetFile
     *
     * @return bool true when the write SHOULD proceed
     */
    private function shouldWriteManagedFile(string $targetFile): bool
    {
        $confirmation = new ConfirmationQuestion(\sprintf('Update managed file %s? [y/N] ', $targetFile), false);

        return $this->io->askQuestion($confirmation);
    }

    /**
     * Normalizes a composer manifest after funding metadata changes.
     *
     * @param string $composerFile the composer manifest path
     * @param OutputInterface $output the command output
     *
     * @return int the normalization status code
     */
    private function normalizeComposerFile(string $composerFile, OutputInterface $output): int
    {
        $processBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--no-update-lock');

        $workingDirectory = $this->filesystem->getDirectory($composerFile);

        if ('.' !== $workingDirectory) {
            $processBuilder = $processBuilder->withArgument('--working-dir', $workingDirectory);
        }

        $composerBasename = $this->filesystem->getBasename($composerFile);

        if ('composer.json' !== $composerBasename) {
            $processBuilder = $processBuilder->withArgument('--file', $composerBasename);
        }

        $this->processQueue->add(
            process: $processBuilder->build('composer normalize'),
            label: 'Normalizing composer.json with Composer Normalize',
        );

        return $this->processQueue->run($output);
    }
}
