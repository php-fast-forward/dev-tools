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

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Composer\Command\BaseCommand;
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

use function Safe\getcwd;

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
final class GitAttributesCommand extends BaseCommand
{
    private const string FILENAME = '.gitattributes';

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
     * @param ComposerJsonInterface $composer the composer.json accessor
     */
    public function __construct(
        private readonly CandidateProviderInterface $candidateProvider,
        private readonly ExistenceCheckerInterface $existenceChecker,
        private readonly ExportIgnoreFilterInterface $exportIgnoreFilter,
        private readonly MergerInterface $merger,
        private readonly ReaderInterface $reader,
        private readonly WriterInterface $writer,
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
    ) {
        parent::__construct();
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

        $basePath = getcwd();
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

        $gitattributesPath = $this->filesystem->getAbsolutePath(self::FILENAME);
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
        $extra = $this->composer->getExtra(self::EXTRA_NAMESPACE);

        return array_unique(array_merge(
            $extra[self::EXTRA_KEEP_IN_EXPORT] ?? [],
            $extra[self::EXTRA_NO_EXPORT_IGNORE] ?? [],
        ));
    }
}
