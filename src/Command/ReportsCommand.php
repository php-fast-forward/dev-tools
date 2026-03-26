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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Coordinates the generation of Fast Forward documentation frontpage and related reports.
 * This class MUST NOT be overridden and SHALL securely combine docs and testing commands.
 */
final class ReportsCommand extends AbstractCommand
{
    /**
     * Configures the metadata for the reports generation command.
     *
     * The method MUST identify the command correctly and describe its intent broadly.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('reports')
            ->setDescription('Generates the frontpage for Fast Forward documentation.')
            ->setHelp(
                'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.'
            );
    }

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
        $results[] = $this->runCommand('docs', [
            '--target' => $docsPath,
        ], $output);

        $output->writeln('<info>Generating test coverage report on path: ' . $coveragePath . '</info>');
        $results[] = $this->runCommand('tests', [
            '--coverage' => $coveragePath,
        ], $output);

        $output->writeln('<info>Frontpage generation completed!</info>');

        return \in_array(self::FAILURE, $results, true) ? self::FAILURE : self::SUCCESS;
    }
}
