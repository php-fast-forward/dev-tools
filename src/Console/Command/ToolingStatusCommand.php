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

use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Tooling\ToolingProfileInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function Safe\json_encode;

/**
 * Reports project selection and the availability of existing tool binaries.
 *
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[AsCommand(
    name: 'tooling:status',
    description: 'Show selected capabilities and available tool binaries.',
    aliases: []
)]
final class ToolingStatusCommand extends Command
{
    use HasJsonOption;

    /**
     * @param ToolingProfileInterface $profile the project profile store
     */
    public function __construct(
        private readonly ToolingProfileInterface $profile
    ) {
        parent::__construct();
    }

    /**
     * Defines structured output options.
     */
    protected function configure(): void
    {
        $this->addJsonOption();
    }

    /**
     * Reads capability metadata without installing or modifying anything.
     *
     * @param InputInterface $input command options
     * @param OutputInterface $output metadata output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $status = $this->profile->status();
            if ($this->isJsonOutput($input)) {
                $output->writeln(json_encode([
                    'version' => 1,
                    'profile' => $this->profile->read(),
                    'capabilities' => $status,
                ], \JSON_THROW_ON_ERROR | ($this->isPrettyJsonOutput($input) ? \JSON_PRETTY_PRINT : 0)));
            } else {
                $rows = [];
                foreach ($status as $capability => $metadata) {
                    $rows[] = [
                        $capability,
                        $metadata['selected'] ? 'yes' : 'no',
                        $metadata['available'] ? 'yes' : 'no',
                        implode(', ', $metadata['missing']),
                    ];
                }
                (new SymfonyStyle($input, $output))->table(
                    ['Capability', 'Selected', 'Available', 'Missing tools'],
                    $rows
                );
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            (new SymfonyStyle($input, $output))->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
