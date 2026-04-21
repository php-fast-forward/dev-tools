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
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRendererInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\OutputFormatResolverInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the rendered notes body for a released changelog version.
 */
#[AsCommand(
    name: 'changelog:show',
    description: 'Prints the notes body for a released changelog version.',
    help: 'This command renders the body of one released changelog section so it can be reused for GitHub release notes.'
)]
final class ChangelogShowCommand extends BaseCommand
{
    /**
     * @param FilesystemInterface $filesystem
     * @param ChangelogManagerInterface $changelogManager
     * @param OutputFormatResolverInterface $outputFormatResolver
     * @param CommandResultRendererInterface $commandResultRenderer
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ChangelogManagerInterface $changelogManager,
        private readonly OutputFormatResolverInterface $outputFormatResolver,
        private readonly CommandResultRendererInterface $commandResultRenderer,
    ) {
        parent::__construct();
    }

    /**
     * Configures the show command arguments and options.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'version',
                mode: InputArgument::REQUIRED,
                description: 'The released version to render.',
            )
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            )
            ->addOption(
                name: 'output-format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: OutputFormat::defaultValue(),
                suggestedValues: OutputFormat::supportedValues(),
            );
    }

    /**
     * Prints the rendered release notes body.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $this->outputFormatResolver->resolve($input);
        $version = (string) $input->getArgument('version');
        $file = (string) $input->getOption('file');
        $releaseNotes = $this->changelogManager->renderReleaseNotes(
            $this->filesystem->getAbsolutePath($file),
            $version,
        );

        if (OutputFormat::TEXT === $format) {
            $output->write($releaseNotes);

            return self::SUCCESS;
        }

        $this->commandResultRenderer->render(
            $output,
            CommandResult::success(
                $releaseNotes,
                [
                    'command' => 'changelog:show',
                    'file' => $file,
                    'version' => $version,
                    'release_notes' => $releaseNotes,
                ],
            ),
            $format,
        );

        return self::SUCCESS;
    }
}
