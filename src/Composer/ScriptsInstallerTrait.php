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

namespace FastForward\DevTools\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Factory;
use Composer\Json\JsonManipulator;

/**
 * Provides a reusable mechanism for installing development scripts into `composer.json`.
 * This trait SHALL be shared between Composer plugins and CLI commands.
 */
trait ScriptsInstallerTrait
{
    /**
     * Installs the required scripts into the root `composer.json` file.
     *
     * This method SHALL ensure that common development tools are easily accessible
     * via `composer run-script` by adding them to the primary `scripts` section.
     * It MUST merge any existing scripts to prevent data loss.
     *
     * @param Composer $composer the primary package configuration instance over Composer
     * @param IOInterface $io interactive communication channels
     *
     * @return void
     */
    private function installScripts(Composer $composer, IOInterface $io): void
    {
        $package = $composer->getPackage();

        if ($package->getName() !== 'fast-forward/dev-tools') {
            return;
        }

        $io->write('<info>fast-forward/dev-tools: Installing scripts into composer.json</info>');

        $file = Factory::getComposerFile();

        if (! file_exists($file)) {
            return;
        }

        $contents = file_get_contents($file);
        $manipulator = new JsonManipulator($contents);

        foreach ([
            'dev-tools' => 'dev-tools',
            'dev-tools:fix' => 'dev-tools --fix',
        ] as $name => $command) {
            $manipulator->addSubNode('scripts', $name, $command);
        }

        file_put_contents($file, $manipulator->getContents());
    }
}
