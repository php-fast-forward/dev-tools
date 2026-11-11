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
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\SelfUpdate\VersionCheckerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_encode;

/**
 * Exposes runtime paths without relying on an executor's private cache layout.
 *
 * The command MUST emit one JSON object without changing the working project.
 */
#[AsCommand(name: 'runtime', description: 'Prints the active DevTools runtime as JSON.', aliases: [])]
final class RuntimeCommand extends Command
{
    use HasJsonOption;

    /**
     * @param VersionCheckerInterface $versionChecker resolves the active package version
     */
    public function __construct(
        private readonly VersionCheckerInterface $versionChecker
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addJsonOption();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(json_encode([
            'binary' => DevToolsPathResolver::getBinaryPath(),
            'autoload' => DevToolsPathResolver::getRuntimeAutoloadPath(),
            'package_root' => DevToolsPathResolver::getPackagePath(),
            'project_root' => WorkingProjectPathResolver::getProjectPath(),
            'version' => $this->versionChecker->getCurrentVersion(),
        ], \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
