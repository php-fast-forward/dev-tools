<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\MergerInterface;
use FastForward\DevTools\GitAttributes\ReaderInterface;
use FastForward\DevTools\GitAttributes\WriterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Provides functionality to manage .gitattributes export-ignore rules.
 *
 * This command adds export-ignore entries for repository-only files and directories
 * to keep them out of Composer package archives.
 */
#[AsCommand(
    name: 'gitattributes',
    description: 'Manages .gitattributes export-ignore rules for leaner package archives.',
    help: 'This command adds export-ignore entries for repository-only files and directories to keep them out of Composer package archives. '
    . 'Only paths that exist in the repository are added, existing custom rules are preserved, and "extra.gitattributes.keep-in-export" paths stay in exported archives.'
)]
final class GitAttributesCommand extends AbstractCommand
{
    private const string EXTRA_NAMESPACE = 'gitattributes';

    private const string EXTRA_KEEP_IN_EXPORT = 'keep-in-export';

    private const string EXTRA_NO_EXPORT_IGNORE = 'no-export-ignore';

    /**
     * Creates a new GitAttributesCommand instance.
     *
     * @param CandidateProviderInterface $candidateProvider the candidate provider
     * @param ExistenceCheckerInterface $existenceChecker the repository path existence checker
     * @param ExportIgnoreFilterInterface $exportIgnoreFilter the configured candidate filter
     * @param MergerInterface $merger the merger component
     * @param ReaderInterface $reader the reader component
     * @param WriterInterface $writer the writer component
     * @param Filesystem $filesystem the filesystem component
     * @param ComposerJson $composerJson the composer.json accessor
     */
    public function __construct(
        private readonly CandidateProviderInterface $candidateProvider,
        private readonly ExistenceCheckerInterface $existenceChecker,
        private readonly ExportIgnoreFilterInterface $exportIgnoreFilter,
        private readonly MergerInterface $merger,
        private readonly ReaderInterface $reader,
        private readonly WriterInterface $writer,
        private readonly ComposerJson $composerJson,
        Filesystem $filesystem,
    ) {
        parent::__construct($filesystem);
    }

    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, and help text for the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Synchronizing .gitattributes export-ignore rules...</info>');

        $basePath = $this->getCurrentWorkingDirectory();
        $keepInExportPaths = $this->configuredKeepInExportPaths();

        $folderCandidates = $this->exportIgnoreFilter->filter($this->candidateProvider->folders(), $keepInExportPaths);
        $fileCandidates = $this->exportIgnoreFilter->filter($this->candidateProvider->files(), $keepInExportPaths);

        $existingFolders = $this->existenceChecker->filterExisting($basePath, $folderCandidates);
        $existingFiles = $this->existenceChecker->filterExisting($basePath, $fileCandidates);

        $entries = [...$existingFolders, ...$existingFiles];

        if ([] === $entries) {
            $output->writeln(
                '<comment>No candidate paths found in repository. Skipping .gitattributes sync.</comment>'
            );

            return self::SUCCESS;
        }

        $gitattributesPath = Path::join($basePath, '.gitattributes');
        $existingContent = $this->reader->read($gitattributesPath);
        $content = $this->merger->merge($existingContent, $entries, $keepInExportPaths);
        $this->writer->write($gitattributesPath, $content);

        $output->writeln(\sprintf(
            '<info>Added %d export-ignore entries to .gitattributes.</info>',
            \count($entries)
        ));

        return self::SUCCESS;
    }

    /**
     * Resolves the consumer-defined paths that MUST stay in exported archives.
     *
     * The preferred configuration key is "extra.gitattributes.keep-in-export".
     * The alternate "extra.gitattributes.no-export-ignore" key remains
     * supported as a compatibility alias.
     *
     * @return list<string> the configured keep-in-export paths
     */
    private function configuredKeepInExportPaths(): array
    {
        $extra = $this->composerJson->getExtra();

        $gitattributesConfig = $extra[self::EXTRA_NAMESPACE] ?? null;

        if (! \is_array($gitattributesConfig)) {
            return [];
        }

        $configuredPaths = [];

        foreach ([self::EXTRA_KEEP_IN_EXPORT, self::EXTRA_NO_EXPORT_IGNORE] as $key) {
            $values = $gitattributesConfig[$key] ?? [];

            if (\is_string($values)) {
                $values = [$values];
            }

            if (! \is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (\is_string($value)) {
                    $configuredPaths[] = $value;
                }
            }
        }

        return array_values(array_unique($configuredPaths));
    }
}
