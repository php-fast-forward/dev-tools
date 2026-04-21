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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Generates and copies LICENSE files to projects.
 *
 * This command generates a LICENSE file if one does not exist and a supported
 * license is declared in composer.json.
 */
#[AsCommand(
    name: 'license',
    description: 'Generates a LICENSE file from composer.json license information.',
    help: 'This command generates a LICENSE file if one does not exist and a supported license is declared in composer.json.'
)]
final class LicenseCommand extends BaseCommand
{
    /**
     * Creates a new LicenseCommand instance.
     *
     * @param GeneratorInterface $generator the generator component
     * @param FilesystemInterface $filesystem the filesystem component
     * @param FileDiffer $fileDiffer
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly GeneratorInterface $generator,
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
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target path for the generated LICENSE file.',
                default: 'LICENSE',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview LICENSE generation without writing the file.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report LICENSE drift and exit non-zero when changes are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before writing LICENSE changes.',
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
     * Executes the license generation process.
     *
     * Generates a LICENSE file if one does not exist and a supported license is declared in composer.json.
     *
     * @param InputInterface $input the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int the status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPath = $this->filesystem->getAbsolutePath($input->getOption('target'));
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');
        $existingContent = $this->filesystem->exists($targetPath) ? $this->filesystem->readFile($targetPath) : null;
        $generatedContent = $this->generator->generateContent();

        if (null === $generatedContent) {
            $this->logger->notice(
                'No supported license found in composer.json or license is unsupported. Skipping LICENSE generation.',
                [
                    'command' => 'license',
                    'target_path' => $targetPath,
                ],
            );

            return self::SUCCESS;
        }

        $comparison = $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            $generatedContent,
            $existingContent,
            null === $existingContent
                ? \sprintf('Managed file %s will be created from generated LICENSE content.', $targetPath)
                : \sprintf('Updating managed file %s from generated LICENSE content.', $targetPath),
        );

        $this->logger->notice(
            $comparison->getSummary(),
            [
                'command' => 'license',
                'target_path' => $targetPath,
            ],
        );

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->logger->notice(
                    $consoleDiff,
                    [
                        'command' => 'license',
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

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteLicense($input, $output, $targetPath)) {
            $this->logger->notice(
                'Skipped updating {target_path}.',
                [
                    'command' => 'license',
                    'target_path' => $targetPath,
                ],
            );

            return self::SUCCESS;
        }

        $this->filesystem->dumpFile($targetPath, $generatedContent);
        $this->logger->info(
            '{file_name} file generated successfully at {target_path}.',
            [
                'command' => 'license',
                'file_name' => basename($targetPath),
                'target_path' => $targetPath,
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Prompts whether the generated LICENSE should be written.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $targetPath the license path that would be written
     *
     * @return bool true when the write SHOULD proceed
     */
    private function shouldWriteLicense(InputInterface $input, OutputInterface $output, string $targetPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Write managed file %s? [y/N] ', $targetPath), false);

        return (bool) $this->getHelper('question')
            ->ask($input, $output, $question);
    }
}
