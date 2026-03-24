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
use Symfony\Component\Process\Process;

final class CodeStyleCommand extends AbstractCommand
{
    public const string CONFIG = 'ecs.php';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('code-style')
            ->setDescription('Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.')
            ->setHelp('This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.')
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code style issues.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running code style checks and fixes...</info>');

        $command = new Process(['composer', 'update', '--lock', '--quiet']);

        parent::runProcess($command, $output);

        $command = new Process(['composer', 'normalize', $input->getOption('fix') ? '--quiet' : '--dry-run']);

        parent::runProcess($command, $output);

        $command = new Process([
            \dirname(__DIR__, 2) . '/vendor/bin/ecs',
            '--config=' . parent::getConfigFile(self::CONFIG),
            $input->getOption('fix') ? '--fix' : '--clear-cache',
        ]);

        return parent::runProcess($command, $output);
    }
}
