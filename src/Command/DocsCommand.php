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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function array_map;
use function implode;
use function ltrim;
use function strtr;

/**
 * Handles the generation of API documentation for the project.
 * This class MUST NOT be extended and SHALL utilize phpDocumentor to accomplish its task.
 */
final class DocsCommand extends AbstractCommand
{
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
            ->setName('docs')
            ->setDescription('Generates API documentation.')
            ->setHelp('This command generates API documentation using phpDocumentor.')
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the output directory for the generated HTML documentation.',
                default: 'public',
            )
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the source directory for the generated HTML documentation.',
                default: 'docs',
            );
    }

    /**
     * Executes the generation of the documentation files.
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

        $source = $this->getAbsolutePath($input->getOption('source'));

        if (! $this->filesystem->exists($source)) {
            $output->writeln(\sprintf('<error>Source directory not found: %s</error>', $source));

            return self::FAILURE;
        }

        $target = $this->getAbsolutePath($input->getOption('target'));

        $htmlConfig = $this->createPhpDocumentorConfig(source: $source, target: $target, template: 'default');

        $command = new Process([$this->getAbsolutePath('vendor/bin/phpdoc'), '--config', $htmlConfig]);

        return parent::runProcess($command, $output);
    }

    /**
     * Creates a temporary phpDocumentor configuration for the current project.
     *
     * @param string $source the source directory for the generated documentation
     * @param string $target the output directory for the generated documentation
     * @param string $template the phpDocumentor template name or path
     *
     * @return string the absolute path to the generated configuration
     */
    private function createPhpDocumentorConfig(string $source, string $target, string $template): string
    {
        $workingDirectory = $this->getCurrentWorkingDirectory();

        $templateFile = parent::getDevToolsFile('resources/phpdocumentor.xml');

        $configDirectory = $this->getAbsolutePath('tmp/cache/phpdoc');
        $configFile = $configDirectory . '/phpdocumentor.xml';

        if (! $this->filesystem->exists($configDirectory)) {
            $this->filesystem->mkdir($configDirectory);
        }

        $psr4Namespaces = $this->getPsr4Namespaces();
        $paths = implode("\n", array_map(
            fn(string $path): string => \sprintf(
                '<path>%s</path>',
                ltrim(str_replace($workingDirectory, '', $path), '/')
            ),
            $psr4Namespaces,
        ));

        $guidePath = Path::makeRelative($source, $workingDirectory);

        $defaultPackageName = array_key_first($psr4Namespaces) ?: '';
        $templateContents = file_get_contents($templateFile);

        $this->filesystem->dumpFile($configFile, strtr($templateContents, [
            '%%TITLE%%' => $this->getProjectDescription(),
            '%%TEMPLATE%%' => $template,
            '%%TARGET%%' => $target,
            '%%WORKING_DIRECTORY%%' => $workingDirectory,
            '%%PATHS%%' => $paths,
            '%%GUIDE_PATH%%' => $guidePath,
            '%%DEFAULT_PACKAGE_NAME%%' => $defaultPackageName,
        ]));

        return $configFile;
    }
}
