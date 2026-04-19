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
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\MergerInterface;
use FastForward\DevTools\GitAttributes\ReaderInterface;
use FastForward\DevTools\GitAttributes\WriterInterface;
use FastForward\DevTools\Resource\OverwriteDiffRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
     * @param FilesystemInterface $filesystem the filesystem component
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
        private readonly OverwriteDiffRenderer $overwriteDiffRenderer,
    ) {
        parent::__construct();
    }

    /**
     * Configures verification and interactive update modes.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview .gitattributes synchronization without writing the file.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report .gitattributes drift and exit non-zero when changes are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before updating .gitattributes.',
            );
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
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

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
        $renderedContent = $this->writer->render($content);
        $comparison = $this->overwriteDiffRenderer->renderContents(
            'generated .gitattributes synchronization',
            $gitattributesPath,
            $renderedContent,
            '' === $existingContent ? null : $this->writer->render($existingContent),
            \sprintf('Updating managed file %s from generated .gitattributes synchronization.', $gitattributesPath),
        );

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

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteGitAttributes($input, $output, $gitattributesPath)) {
            $output->writeln(\sprintf('<comment>Skipped updating %s.</comment>', $gitattributesPath));

            return self::SUCCESS;
        }

        $this->writer->write($gitattributesPath, $content);

        $output->writeln(\sprintf(
            '<info>Added %d export-ignore entries to .gitattributes.</info>',
            \count($entries)
        ));

        return self::SUCCESS;
    }

    /**
     * Prompts whether .gitattributes should be updated.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $targetPath the target path that would be updated
     *
     * @return bool true when the update SHOULD proceed
     */
    private function shouldWriteGitAttributes(InputInterface $input, OutputInterface $output, string $targetPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Update managed file %s? [y/N] ', $targetPath), false);

        return (bool) $this->getHelper('question')->ask($input, $output, $question);
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
