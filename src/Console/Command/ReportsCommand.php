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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Coordinates the generation of Fast Forward documentation frontpage and related reports.
 * This class MUST NOT be overridden and SHALL securely combine docs and testing commands.
 */
#[AsCommand(
    name: 'reports',
    description: 'Generates the frontpage for Fast Forward documentation.',
    help: 'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.'
)]
final class ReportsCommand extends AbstractCommand
{
    /**
     * Executes the generation logic for diverse reports.
     *
     * The method MUST run the underlying `docs` and `tests` commands. It SHALL process
     * and generate the frontpage output file successfully.
     *
     * @param InputInterface $input the structured inputs holding specific arguments
     * @param OutputInterface $output the designated output interface
     *
     * @return int the integer outcome from the base process execution
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>');

        $docsPath = $this->getAbsolutePath('public');
        $coveragePath = $this->getAbsolutePath('public/coverage');

        $results = [];

        $output->writeln('<info>Generating API documentation on path: ' . $docsPath . '</info>');
        $results[] = $this->runCommand('docs --target=' . $docsPath, $output);

        $output->writeln('<info>Generating test coverage report on path: ' . $coveragePath . '</info>');
        $results[] = $this->runCommand('tests --coverage=' . $coveragePath, $output);

        return \in_array(self::FAILURE, $results, true) ? self::FAILURE : self::SUCCESS;
    }
}
