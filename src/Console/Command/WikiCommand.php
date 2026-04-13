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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Handles the generation of API documentation for the project.
 * This class MUST NOT be extended and SHALL utilize phpDocumentor to accomplish its task.
 */
#[AsCommand(
    name: 'wiki',
    description: 'Generates API documentation in Markdown format.',
    help: 'This command generates API documentation in Markdown format using phpDocumentor. '
    . 'It accepts an optional `--target` option to specify the output directory for the generated documentation.'
)]
final class WikiCommand extends AbstractCommand
{
    /**
     * Creates a new WikiCommand instance.
     *
     * @param ComposerJson $composerJson the composer.json accessor
     * @param Filesystem $filesystem the filesystem component
     */
    public function __construct(
        private readonly ComposerJson $composerJson,
        Filesystem $filesystem
    ) {
        return parent::__construct($filesystem);
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
        $output->writeln('<info>Generating API documentation...</info>');

        $arguments = [
            $this->getAbsolutePath('vendor/bin/phpdoc'),
            '--cache-folder',
            $this->getCurrentWorkingDirectory() . '/tmp/cache/phpdoc',
            '--visibility=public,protected',
            '--title=' . $this->composerJson->getPackageDescription(),
        ];

        $psr4Namespaces = $this->composerJson->getAutoload();

        foreach ($psr4Namespaces as $path) {
            $arguments[] = '--directory';
            $arguments[] = $path;
        }

        if ($defaultPackageName = array_key_first($psr4Namespaces)) {
            $arguments[] = '--defaultpackagename';
            $arguments[] = $defaultPackageName;
        }

        $command = new Process([
            ...$arguments,
            '--target',
            $this->getAbsolutePath($input->getOption('target')),
            '--template',
            'vendor/saggre/phpdocumentor-markdown/themes/markdown',
        ]);

        return parent::runProcess($command, $output);
    }
}
