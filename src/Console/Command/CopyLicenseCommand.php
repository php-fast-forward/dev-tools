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

use Composer\Factory;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\PlaceholderResolver;
use FastForward\DevTools\License\Reader;
use FastForward\DevTools\License\Resolver;
use FastForward\DevTools\License\TemplateLoader;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

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
final class CopyLicenseCommand extends AbstractCommand
{
    /**
     * Creates a new CopyLicenseCommand instance.
     *
     * @param GeneratorInterface $generator the generator component
     * @param Filesystem $filesystem the filesystem component
     */
    public function __construct(
        private readonly GeneratorInterface $generator,
        Filesystem $filesystem,
    ) {
        parent::__construct($filesystem);
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
        $targetPath = $this->getConfigFile('LICENSE', true);

        if ($this->filesystem->exists($targetPath)) {
            $output->writeln('<info>LICENSE file already exists. Skipping generation.</info>');

            return self::SUCCESS;
        }

        $license = $this->generator->generate($targetPath);

        if (null === $license) {
            $output->writeln(
                '<comment>No supported license found in composer.json or license is unsupported. Skipping LICENSE generation.</comment>'
            );

            return self::SUCCESS;
        }

        $output->writeln('<info>LICENSE file generated successfully.</info>');

        return self::SUCCESS;
    }
}
