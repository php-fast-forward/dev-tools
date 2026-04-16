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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Command;

use Composer\Command\BaseCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\License\GeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates and copies LICENSE files to projects.
 *
 * This command generates a LICENSE file if one does not exist and a supported
 * license is declared in composer.json.
 */
#[AsCommand(
    name: 'license',
    description: 'Generates a LICENSE file from composer.json license information.',
    help: 'This command generates a LICENSE file if one does not exist and a supported license is declared in composer.json.'
)]
final class LicenseCommand extends BaseCommand
{
    /**
     * Creates a new LicenseCommand instance.
     *
     * @param GeneratorInterface $generator the generator component
     * @param FilesystemInterface $filesystem the filesystem component
     */
    public function __construct(
        private readonly GeneratorInterface $generator,
        private readonly FilesystemInterface $filesystem,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target path for the generated LICENSE file.',
                default: 'LICENSE',
            );
    }

    /**
     * Executes the license generation process.
     *
     * Generates a LICENSE file if one does not exist and a supported license is declared in composer.json.
     *
     * @param InputInterface $input the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int the status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPath = $this->filesystem->getAbsolutePath($input->getOption('target'));

        if ($this->filesystem->exists($targetPath)) {
            $output->writeln(
                \sprintf('<info>%s file already exists at %s. Skipping generation.</info>', basename(
                    $targetPath
                ), $targetPath)
            );

            return self::SUCCESS;
        }

        $license = $this->generator->generate($targetPath);

        if (null === $license) {
            $output->writeln(
                '<comment>No supported license found in composer.json or license is unsupported. Skipping LICENSE generation.</comment>'
            );

            return self::SUCCESS;
        }

        $output->writeln(
            \sprintf('<info>%s file generated successfully at %s.</info>', basename($targetPath), $targetPath)
        );

        return self::SUCCESS;
    }
}
