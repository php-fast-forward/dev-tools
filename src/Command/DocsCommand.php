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

final class DocsCommand extends AbstractCommand
{
    /**
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
        $output->writeln('<info>Generating API documentation...</info>');

        $arguments = [
            $this->getAbsolutePath('vendor/bin/phpdoc'),
            '--cache-folder',
            $this->getCurrentWorkingDirectory() . '/tmp/cache/phpdoc',
        ];

        $psr4Namespaces = $this->getPsr4Namespaces();

        foreach ($psr4Namespaces as $path) {
            $arguments[] = '--directory';
            $arguments[] = $path;
        }

        if ($defaultPackageName = array_key_first($psr4Namespaces)) {
            $arguments[] = '--defaultpackagename';
            $arguments[] = $defaultPackageName;
        }

        $title = $this->getTitle();

        if ('' !== $title && '0' !== $title) {
            $arguments[] = '--title';
            $arguments[] = $title;
        }

        $command = new Process([
            ...$arguments,
            '--target',
            $this->getCurrentWorkingDirectory() . '/docs/wiki',
            '--template',
            'vendor/saggre/phpdocumentor-markdown/themes/markdown',
        ]);

        $resultWiki = parent::runProcess($command, $output);

        if (self::FAILURE === $resultWiki) {
            return self::FAILURE;
        }

        if ($input->getOption('target')) {
            $command = new Process([...$arguments, '--target', $this->getAbsolutePath($input->getOption('target'))]);

            return parent::runProcess($command, $output);
        }

        return $resultWiki;
    }
}
