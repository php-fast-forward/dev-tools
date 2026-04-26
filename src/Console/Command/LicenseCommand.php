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
use FastForward\DevTools\License\GeneratorInterface;
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
 * Generates and copies LICENSE files to projects.
 *
 * This command generates a LICENSE file if one does not exist and a supported
 * license is declared in composer.json.
 */
#[AsCommand(
    name: 'license:generate',
    description: 'Generates a LICENSE file from composer.json license information.',
    aliases: ['LICENSE.md', 'license'],
)]
final class LicenseCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new LicenseCommand instance.
     *
     * @param GeneratorInterface $generator the generator component
     * @param FilesystemInterface $filesystem the filesystem component
     * @param FileDiffer $fileDiffer
     * @param LoggerInterface $logger the output-aware logger
     * @param SymfonyStyle $io
     */
    public function __construct(
        private readonly GeneratorInterface $generator,
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
            'This command generates a LICENSE file if one does not exist and a supported license is declared in'
            . ' composer.json.'
        );

        $this->addJsonOption()
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
            $this->notice(
                'No supported license found in composer.json or license is unsupported. Skipping LICENSE generation.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
            );

            return $this->success(
                'LICENSE generation was skipped because no supported license metadata was available.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                'notice',
            );
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

        $this->logger->notice($comparison->getSummary(), [
            'input' => $input,
            'target_path' => $targetPath,
        ]);

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->logger->notice(
                    $consoleDiff,
                    [
                        'input' => $input,
                        'target_path' => $targetPath,
                        'diff' => $comparison->getDiff(),
                    ],
                );
            }
        }

        if ($comparison->isUnchanged()) {
            return $this->success(
                'LICENSE already matches the generated content.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
            );
        }

        if ($check) {
            return $this->failure(
                'LICENSE requires synchronization updates.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                $targetPath,
            );
        }

        if ($dryRun) {
            return $this->success(
                'LICENSE generation preview completed.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                'notice',
            );
        }

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteLicense($targetPath)) {
            $this->notice('Skipped updating {target_path}.', $input, [
                'target_path' => $targetPath,
            ]);

            return $this->success(
                'LICENSE generation was skipped.',
                $input,
                [
                    'target_path' => $targetPath,
                ],
                'notice',
            );
        }

        $this->filesystem->dumpFile($targetPath, $generatedContent);

        return $this->success(
            '{file_name} file generated successfully at {target_path}.',
            $input,
            [
                'file_name' => basename($targetPath),
                'target_path' => $targetPath,
            ],
        );
    }

    /**
     * Prompts whether the generated LICENSE should be written.
     *
     * @param string $targetPath the license path that would be written
     *
     * @return bool true when the write SHOULD proceed
     */
    private function shouldWriteLicense(string $targetPath): bool
    {
        $confirmation = new ConfirmationQuestion(\sprintf('Write managed file %s? [y/N] ', $targetPath), false);

        return $this->io->askQuestion($confirmation);
    }
}
