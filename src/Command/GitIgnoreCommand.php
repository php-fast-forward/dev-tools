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

use FastForward\DevTools\GitIgnore\Merger;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\Reader;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\Writer;
use FastForward\DevTools\GitIgnore\WriterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides functionality to merge and synchronize .gitignore files.
 *
 * This command merges the canonical .gitignore from dev-tools with the project's
 * existing .gitignore, removing duplicates and sorting entries.
 *
 * The command accepts two options: --source and --target to specify the paths
 * to the canonical and project .gitignore files respectively.
 */
final class GitIgnoreCommand extends AbstractCommand
{
    private readonly WriterInterface $writer;

    /**
     * Creates a new GitIgnoreCommand instance.
     *
     * @param Filesystem|null $filesystem the filesystem component
     * @param MergerInterface $merger the merger component
     * @param ReaderInterface $reader the reader component
     * @param WriterInterface|null $writer the writer component
     */
    public function __construct(
        ?Filesystem $filesystem = null,
        private readonly MergerInterface $merger = new Merger(),
        private readonly ReaderInterface $reader = new Reader(),
        ?WriterInterface $writer = null
    ) {
        parent::__construct($filesystem);
        $this->writer = $writer ?? new Writer($this->filesystem);
    }

    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, and help text for the command.
     * It SHALL identify the tool as the mechanism for script synchronization.
     */
    protected function configure(): void
    {
        $this
            ->setName('gitignore')
            ->setDescription('Merges and synchronizes .gitignore files.')
            ->setHelp(
                "This command merges the canonical .gitignore from dev-tools with the project's existing .gitignore."
            )
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the source .gitignore file (canonical)',
                default: parent::getDevToolsFile('.gitignore'),
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the target .gitignore file (project)',
                default: parent::getConfigFile('.gitignore', true)
            );
    }

    /**
     * Executes the gitignore merge process.
     *
     * @param InputInterface $input the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int the status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Merging .gitignore files...</info>');

        $sourcePath = $input->getOption('source');
        $targetPath = $input->getOption('target');

        $canonical = $this->reader->read($sourcePath);
        $project = $this->reader->read($targetPath);

        $merged = $this->merger->merge($canonical, $project);

        $this->writer->write($merged);

        $output->writeln('<info>Successfully merged .gitignore file.</info>');

        return self::SUCCESS;
    }
}
