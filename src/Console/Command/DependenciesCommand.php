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
use Symfony\Component\Process\Process;

/**
 * Orchestrates dependency analysis across the supported Composer analyzers.
 * This command MUST report missing and unused dependencies using a single,
 * deterministic report that is friendly for local development and CI runs.
 */
#[AsCommand(name: 'dependencies', description: 'Analyzes missing and unused Composer dependencies.', aliases: [
    'deps',
], help: 'This command runs composer-dependency-analyser and composer-unused to report missing and unused Composer dependencies.')]
final class DependenciesCommand extends AbstractCommand
{
    /**
     * Executes the dependency analysis workflow.
     *
     * The command MUST verify the required binaries before executing the tools,
     * SHOULD normalize their machine-readable output into a unified report, and
     * SHALL return a non-zero exit code when findings or execution failures exist.
     *
     * @param InputInterface $input the runtime command input
     * @param OutputInterface $output the console output stream
     *
     * @return int the command execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running dependency analysis...</info>');

        $composerJson = $this->getConfigFile('composer.json');

        $results[] = $this->runProcess(
            new Process(['vendor/bin/composer-unused', $composerJson, '--no-progress']),
            $output
        );
        $results[] = $this->runProcess(new Process([
            'vendor/bin/composer-dependency-analyser',
            '--composer-json=' . $composerJson,
            '--ignore-unused-deps',
            '--ignore-prod-only-in-dev-deps',
        ]), $output);

        return \in_array(self::FAILURE, $results, true) ? self::FAILURE : self::SUCCESS;
    }
}
