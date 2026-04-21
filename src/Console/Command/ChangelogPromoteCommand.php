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
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Promotes the Unreleased section into a published changelog version.
 */
#[AsCommand(
    name: 'changelog:promote',
    description: 'Promotes Unreleased entries into a published changelog version.',
    help: 'This command moves the current Unreleased entries into a released version section, records the release date, and restores an empty Unreleased section.'
)]
final class ChangelogPromoteCommand extends BaseCommand
{
    /**
     * @param FilesystemInterface $filesystem
     * @param ChangelogManagerInterface $changelogManager
     * @param ClockInterface $clock
     * @param CommandResponderFactoryInterface $commandResponderFactory
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ChangelogManagerInterface $changelogManager,
        private readonly ClockInterface $clock,
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

    /**
     * Configures the promotion arguments and options.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'version',
                mode: InputArgument::REQUIRED,
                description: 'The semantic version that should receive the current Unreleased entries.',
            )
            ->addOption(
                name: 'date',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The release date to record in YYYY-MM-DD format.',
            )
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            )
            ->addOption(
                name: 'format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: OutputFormat::defaultValue(),
                suggestedValues: OutputFormat::supportedValues(),
            );
    }

    /**
     * Promotes unreleased entries into the requested version section.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responder = $this->commandResponderFactory->from($input, $output);
        $file = $this->filesystem->getAbsolutePath((string) $input->getOption('file'));
        $version = (string) $input->getArgument('version');
        $date = (string) ($input->getOption('date') ?: $this->clock->now()->format('Y-m-d'));

        $this->changelogManager->promote($file, $version, $date);

        return $responder->success(
            \sprintf('Promoted Unreleased changelog entries to [%s] in %s.', $version, $file),
            [
                'command' => 'changelog:promote',
                'file' => (string) $input->getOption('file'),
                'version' => $version,
                'date' => $date,
            ],
        );
    }
}
