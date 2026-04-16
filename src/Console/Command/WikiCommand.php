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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Command;

use Composer\Command\BaseCommand;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

/**
 * Handles the generation of API documentation for the project.
 * This class MUST NOT be extended and SHALL utilize phpDocumentor to accomplish its task.
 */
#[AsCommand(
    name: 'wiki',
    description: 'Generates API documentation in Markdown format.',
    help: 'This command generates API documentation in Markdown format using phpDocumentor. '
    . 'It accepts an optional `--target` option to specify the output directory and `--init` to initialize the wiki submodule.'
)]
final class WikiCommand extends BaseCommand
{
    /**
     * Creates a new WikiCommand instance.
     *
     * @param ComposerJsonInterface $composer the composer.json accessor
     * @param ProcessBuilderInterface $processBuilder
     * @param ProcessQueueInterface $processQueue
     * @param FilesystemInterface $filesystem the filesystem used to inspect the wiki target
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
    ) {
        return parent::__construct();
    }

    /**
     * Configures the command instance.
     *
     * The method MUST set up the name and description. It MAY accept an optional `--target` option
     * pointing to an alternative configuration target path.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the output directory for the generated Markdown documentation.',
                default: '.github/wiki'
            )
            ->addOption(
                name: 'cache-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the cache directory for phpDocumentor.',
                default: 'tmp/cache/phpdoc'
            )
            ->addOption(
                name: 'init',
                mode: InputOption::VALUE_NONE,
                description: 'Initialize the configured wiki target as a Git submodule.',
            );
    }

    /**
     * Executes the generation of the documentation files in Markdown format.
     *
     * This method MUST compile arguments based on PSR-4 namespaces to feed into phpDocumentor.
     * It SHOULD provide feedback on generation progress, and SHALL return `self::SUCCESS` on success.
     *
     * @param InputInterface $input the input details for the command
     * @param OutputInterface $output the output mechanism for logging
     *
     * @return int the final execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('init')) {
            return $this->initializeWikiSubmodule((string) $input->getOption('target'), $output);
        }

        $output->writeln('<info>Generating API documentation...</info>');

        $processBuilder = $this->processBuilder
            ->withArgument('--visibility', 'public,protected')
            ->withArgument('--template', 'vendor/saggre/phpdocumentor-markdown/themes/markdown')
            ->withArgument('--title', $this->composer->getDescription())
            ->withArgument('--target', $input->getOption('target'))
            ->withArgument('--cache-folder', $input->getOption('cache-dir'));

        $psr4Namespaces = $this->composer->getAutoload('psr-4');

        foreach ($psr4Namespaces as $path) {
            $processBuilder = $processBuilder->withArgument('--directory', $path);
        }

        if ($defaultPackageName = array_key_first($psr4Namespaces)) {
            $processBuilder = $processBuilder->withArgument('--defaultpackagename', $defaultPackageName);
        }

        $this->processQueue->add($processBuilder->build('vendor/bin/phpdoc'));

        return $this->processQueue->run();
    }

    /**
     * Adds the repository wiki as a Git submodule when the target path is missing.
     *
     * @param string $target the configured wiki target path
     * @param OutputInterface $output the output used for process feedback
     *
     * @return int the command status code
     */
    private function initializeWikiSubmodule(string $target, OutputInterface $output): int
    {
        $wikiSubmodulePath = (string) $this->filesystem->getAbsolutePath($target);

        if ($this->filesystem->exists($wikiSubmodulePath)) {
            $output->writeln(\sprintf('<info>Wiki submodule already exists at %s.</info>', $wikiSubmodulePath));

            return self::SUCCESS;
        }

        $repositoryUrl = $this->getGitRepositoryUrl();
        $wikiRepoUrl = str_replace('.git', '.wiki.git', $repositoryUrl);

        $this->processQueue->add(
            $this->processBuilder
                ->withArgument('submodule')
                ->withArgument('add')
                ->withArgument($wikiRepoUrl)
                ->withArgument(Path::makeRelative($wikiSubmodulePath, getcwd()))
                ->build('git')
        );

        return $this->processQueue->run($output);
    }

    /**
     * Resolves the current repository remote origin URL.
     *
     * @return string the Git remote origin URL
     */
    private function getGitRepositoryUrl(): string
    {
        $process = $this->processBuilder
            ->withArgument('config')
            ->withArgument('--get')
            ->withArgument('remote.origin.url')
            ->build('git');

        $process->mustRun();

        return trim($process->getOutput());
    }
}
