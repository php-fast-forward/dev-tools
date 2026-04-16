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

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\Json\JsonManipulator;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

/**
 * Updates composer.json with the Fast Forward dev-tools integration metadata.
 */
#[AsCommand(
    name: 'composer-json:update',
    description: 'Updates composer.json with Fast Forward dev-tools scripts and metadata.',
    help: 'This command adds or updates composer.json scripts and GrumPHP extra configuration required by dev-tools.'
)]
final class UpdateComposerJsonCommand extends BaseCommand
{
    /**
     * Creates a new UpdateComposerJsonCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used to read and write composer.json
     * @param FileLocatorInterface $fileLocator the locator used to resolve packaged configuration files
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
    ) {
        parent::__construct();
    }

    /**
     * Configures the composer file option.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'file',
                shortcut: 'f',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the composer.json file to update.',
                default: Factory::getComposerFile(),
            );
    }

    /**
     * Updates composer.json when the target file exists.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getOption('file');

        if (! $this->filesystem->exists($file)) {
            $output->writeln(\sprintf('<comment>Composer file %s does not exist.</comment>', $file));

            return self::SUCCESS;
        }

        $manipulator = new JsonManipulator($this->filesystem->readFile($file));
        $grumphpConfig = $this->fileLocator->locate('grumphp.yml', \dirname(__DIR__, 3));

        foreach ($this->scripts() as $name => $command) {
            $manipulator->addSubNode('scripts', $name, $command);
        }

        $manipulator->addSubNode('extra', 'grumphp', [
            'config-default-path' => Path::makeRelative($grumphpConfig, getcwd()),
        ], true);

        $this->filesystem->dumpFile($file, $manipulator->getContents());
        $output->writeln('<info>Updated composer.json dev-tools configuration.</info>');

        return self::SUCCESS;
    }

    /**
     * Returns the Composer scripts managed by this command.
     *
     * @return array<string, string> the script name to command map
     */
    private function scripts(): array
    {
        return [
            'dev-tools' => 'dev-tools',
            'dev-tools:fix' => '@dev-tools --fix',
        ];
    }
}
