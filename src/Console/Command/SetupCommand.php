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

use FastForward\DevTools\Tooling\ToolingProfile;
use FastForward\DevTools\Tooling\ToolingProfileInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function Safe\json_encode;

/**
 * Selects project capabilities without installing their runtime dependencies.
 *
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[AsCommand(name: 'setup', description: 'Select project tooling capabilities without installing tools.', aliases: [
    'tooling:configure',
])]
final class SetupCommand extends Command
{
    /**
     * @param ToolingProfileInterface $profile the project profile store
     */
    public function __construct(
        private readonly ToolingProfileInterface $profile
    ) {
        parent::__construct();
    }

    /**
     * Defines explicit selection and preview options.
     */
    protected function configure(): void
    {
        $this->addOption(
            'capability',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Select a capability; repeat for multiple capabilities.'
        )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview the profile without writing it.');
    }

    /**
     * Validates the existing file before any write or interactive selection.
     *
     * @param InputInterface $input command arguments and options
     * @param OutputInterface $output command output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $current = $this->profile->read();
            $selected = $input->getOption('capability');
            if ([] === $selected) {
                if (! $input->isInteractive()) {
                    $io->error('Choose at least one --capability in non-interactive mode.');

                    return self::FAILURE;
                }
                $defaults = array_keys(
                    array_intersect(ToolingProfile::CAPABILITIES, $current ?? ToolingProfile::CAPABILITIES)
                );
                $selected = $io->choice(
                    'Select project capabilities',
                    ToolingProfile::CAPABILITIES,
                    [] === $defaults ? null : implode(',', $defaults),
                    true
                );
            }
            $selected = ToolingProfile::normalize($selected);
            $changed = $this->profile->configure($selected, (bool) $input->getOption('dry-run'));
            $output->writeln(json_encode([
                'version' => 1,
                'capabilities' => $selected,
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
            $io->success(
                $input->getOption(
                    'dry-run'
                ) ? 'Profile preview; no files changed.' : ($changed ? 'Project profile saved; no tools installed.' : 'Project profile unchanged.')
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
