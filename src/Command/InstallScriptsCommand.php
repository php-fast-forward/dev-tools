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

use Composer\Factory;
use Composer\Json\JsonManipulator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Represents the command responsible for installing development scripts into `composer.json`.
 * This class MUST NOT be overridden and SHALL rely on the `ScriptsInstallerTrait`.
 */
final class InstallScriptsCommand extends AbstractCommand
{
    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, and help text for the command.
     * It SHALL identify the tool as the mechanism for script synchronization.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('install-scripts')
            ->setDescription('Installs and synchronizes development scripts in the root composer.json.')
            ->setHelp('This command adds common development tool scripts to your composer.json file.');
    }

    /**
     * Executes the script installation block.
     *
     * The method MUST leverage the `ScriptsInstallerTrait` to update the configuration.
     * It SHALL return `self::SUCCESS` upon completion.
     *
     * @param InputInterface $input the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int the status code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting script installation...</info>');

        $file = Factory::getComposerFile();

        if (! $this->filesystem->exists($file)) {
            return self::FAILURE;
        }

        $contents = $this->filesystem->readFile($file);
        $manipulator = new JsonManipulator($contents);

        $scripts = [
            'dev-tools' => 'dev-tools',
            'dev-tools:fix' => '@dev-tools --fix',
        ];

        $extra = [
            'grumphp' => [
                'config-default-path' => Path::makeRelative(
                    \dirname(__DIR__, 2) . '/grumphp.yml',
                    $this->getCurrentWorkingDirectory(),
                ),
            ],
        ];

        foreach ($scripts as $name => $command) {
            $manipulator->addSubNode('scripts', $name, $command);
        }

        foreach ($extra as $name => $config) {
            $manipulator->addSubNode('extra', $name, $config, true);
        }

        $this->filesystem->dumpFile($file, $manipulator->getContents());

        return self::SUCCESS;
    }
}
