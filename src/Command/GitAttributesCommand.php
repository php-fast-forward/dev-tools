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

namespace FastForward\DevTools\Command;

use FastForward\DevTools\GitAttributes\CandidateProvider;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use FastForward\DevTools\GitAttributes\Merger;
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
final class GitAttributesCommand extends AbstractCommand
{
    /**
     * Creates a new GitAttributesCommand instance.
     *
     * @param Filesystem|null $filesystem the filesystem component
     * @param CandidateProviderInterface|null $candidateProvider the candidate provider
     */
    public function __construct(
        ?Filesystem $filesystem = null,
        private readonly ?CandidateProviderInterface $candidateProvider = new CandidateProvider()
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

        /** @var ExistenceChecker $checker */
        $checker = new ExistenceChecker($basePath, $this->filesystem);

        $existingFolders = $checker->filterExisting($this->candidateProvider->folders());
        $existingFiles = $checker->filterExisting($this->candidateProvider->files());

        sort($existingFolders, \SORT_STRING);
        sort($existingFiles, \SORT_STRING);

        $entries = [...$existingFolders, ...$existingFiles];

        if ([] === $entries) {
            $output->writeln(
                '<comment>No candidate paths found in repository. Skipping .gitattributes sync.</comment>'
            );

            return self::SUCCESS;
        }

        $gitattributesPath = Path::join($basePath, '.gitattributes');
        $merger = new Merger($gitattributesPath);

        $content = $merger->merge($entries);
        $merger->write($content);

        $output->writeln(\sprintf(
            '<info>Added %d export-ignore entries to .gitattributes.</info>',
            \count($entries)
        ));

        return self::SUCCESS;
    }

    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, and help text for the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('gitattributes')
            ->setDescription('Manages .gitattributes export-ignore rules for leaner package archives.')
            ->setHelp(
                'This command adds export-ignore entries for repository-only files and directories '
                . 'to keep them out of Composer package archives. Only paths that exist in the '
                . 'repository are added, and existing custom rules are preserved.'
            );
    }
}
