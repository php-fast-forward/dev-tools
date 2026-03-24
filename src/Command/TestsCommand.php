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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class TestsCommand extends AbstractCommand
{
    public const string CONFIG = 'phpunit.xml';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('tests')
            ->setDescription('Runs PHPUnit tests.')
            ->setHelp('This command runs PHPUnit to execute your tests.')
            ->addArgument(
                name: 'path',
                mode: InputArgument::OPTIONAL,
                description: 'Path to the tests directory.',
                default: './tests',
            )
            ->addOption(
                name: 'bootstrap',
                shortcut: 'b',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the bootstrap file.',
                default: './vendor/autoload.php',
            )
            ->addOption(
                name: 'cache-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the PHPUnit cache directory.',
                default: './tmp/cache/phpunit',
            )
            ->addOption(
                name: 'no-cache',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to disable PHPUnit caching.',
            )
            ->addOption(
                name: 'coverage',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Whether to generate code coverage reports.',
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
        $output->writeln('<info>Running PHPUnit tests...</info>');

        $arguments = [
            $this->getAbsolutePath('vendor/bin/phpunit'),
            '--configuration=' . parent::getConfigFile(self::CONFIG),
            '--bootstrap=' . $this->resolvePath($input, 'bootstrap'),
        ];

        if (! $input->getOption('no-cache')) {
            $arguments[] = '--cache-directory=' . $this->resolvePath($input, 'cache-dir');
        }

        if ($input->getOption('coverage')) {
            $output->writeln(
                '<info>Generating code coverage reports on path: ' . $this->resolvePath($input, 'coverage') . '</info>'
            );

            foreach ($this->getPsr4Namespaces() as $path) {
                $arguments[] = '--coverage-filter=' . $this->getAbsolutePath($path);
            }

            $arguments[] = '--coverage-text';
            $arguments[] = '--coverage-html=' . $this->resolvePath($input, 'coverage');
            $arguments[] = '--testdox-html=' . $this->resolvePath($input, 'coverage') . '/testdox.html';
            $arguments[] = '--coverage-clover=' . $this->resolvePath($input, 'coverage') . '/clover.xml';
            $arguments[] = '--coverage-php=' . $this->resolvePath($input, 'coverage') . '/coverage.php';
        }

        $command = new Process([...$arguments, $input->getArgument('path')]);

        return parent::runProcess($command, $output);
    }

    /**
     * @param InputInterface $input
     * @param string $option
     *
     * @return string
     */
    private function resolvePath(InputInterface $input, string $option): string
    {
        return $this->getAbsolutePath($input->getOption($option));
    }
}
