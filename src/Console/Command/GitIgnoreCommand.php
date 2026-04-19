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
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\WriterInterface;
use FastForward\DevTools\Resource\OverwriteDiffRenderer;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides functionality to merge and synchronize .gitignore files.
 *
 * This command merges the canonical .gitignore from dev-tools with the project's
 * existing .gitignore, removing duplicates and sorting entries.
 *
 * The command accepts two options: --source and --target to specify the paths
 * to the canonical and project .gitignore files respectively.
 */
#[AsCommand(
    name: 'gitignore',
    description: 'Merges and synchronizes .gitignore files.',
    help: "This command merges the canonical .gitignore from dev-tools with the project's existing .gitignore."
)]
final class GitIgnoreCommand extends BaseCommand
{
    /**
     * @var string the default filename for .gitignore files
     */
    public const string FILENAME = '.gitignore';

    /**
     * Creates a new GitIgnoreCommand instance.
     *
     * @param MergerInterface $merger the merger component
     * @param ReaderInterface $reader the reader component
     * @param WriterInterface|null $writer the writer component
     * @param FileLocatorInterface $fileLocator the file locator
     */
    public function __construct(
        private readonly MergerInterface $merger,
        private readonly ReaderInterface $reader,
        private readonly WriterInterface $writer,
        private readonly FileLocatorInterface $fileLocator,
        private readonly OverwriteDiffRenderer $overwriteDiffRenderer,
    ) {
        parent::__construct();
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
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the source .gitignore file (canonical)',
                default: $this->fileLocator->locate(self::FILENAME, \dirname(__DIR__, 3)),
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the target .gitignore file (project)',
                default: $this->fileLocator->locate(self::FILENAME)
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview .gitignore synchronization without writing the file.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report .gitignore drift and exit non-zero when changes are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before updating .gitignore.',
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
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        $canonical = $this->reader->read($sourcePath);
        $project = $this->reader->read($targetPath);

        $merged = $this->merger->merge($canonical, $project);
        $comparison = $this->overwriteDiffRenderer->renderContents(
            'generated .gitignore synchronization',
            $merged->path(),
            $this->writer->render($merged),
            $this->writer->render($project),
            \sprintf('Updating managed file %s from generated .gitignore synchronization.', $merged->path()),
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

        if ($interactive && $input->isInteractive() && ! $this->shouldWriteGitIgnore($input, $output, $merged->path())) {
            $output->writeln(\sprintf('<comment>Skipped updating %s.</comment>', $merged->path()));

            return self::SUCCESS;
        }

        $this->writer->write($merged);

        $output->writeln('<info>Successfully merged .gitignore file.</info>');

        return self::SUCCESS;
    }

    /**
     * Prompts whether .gitignore should be updated.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     * @param string $targetPath the target path that would be updated
     *
     * @return bool true when the update SHOULD proceed
     */
    private function shouldWriteGitIgnore(InputInterface $input, OutputInterface $output, string $targetPath): bool
    {
        $question = new ConfirmationQuestion(\sprintf('Update managed file %s? [y/N] ', $targetPath), false);

        return (bool) $this->getHelper('question')->ask($input, $output, $question);
    }
}
