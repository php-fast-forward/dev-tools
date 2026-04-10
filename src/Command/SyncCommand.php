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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;

/**
 * Represents the command responsible for installing development scripts into `composer.json`.
 * This class MUST NOT be overridden and SHALL rely on the `ScriptsInstallerTrait`.
 */
final class SyncCommand extends AbstractCommand
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
            ->setName('dev-tools:sync')
            ->setDescription(
                'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, and .editorconfig in the root project.'
            )
            ->setHelp(
                'This command adds or updates dev-tools scripts in composer.json, copies reusable GitHub Actions workflows, and ensures .editorconfig is present and up to date.'
            );
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

        $this->updateComposerJson();
        $this->createGitHubActionWorkflows();
        $this->copyEditorConfig();
        $this->copyDependabotConfig();
        $this->addRepositoryWikiGitSubmodule();
        $this->runCommand('gitignore', $output);
        $this->runCommand('skills', $output);
        $this->runCommand('license', $output);

        return self::SUCCESS;
    }

    /**
     * Updates the root composer.json file with required scripts and extra configuration.
     *
     * This method adds or updates the dev-tools scripts and extra configuration for tools like grumphp.
     * It does nothing if the composer.json file does not exist.
     *
     * @return void
     */
    private function updateComposerJson(): void
    {
        $file = Factory::getComposerFile();

        if (! $this->filesystem->exists($file)) {
            return;
        }

        $contents = file_get_contents($file);
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
    }

    /**
     * Creates GitHub Actions workflow templates in the consumer repository.
     *
     * This method copies all .yml workflow templates from resources/github-actions to .github/workflows,
     * unless the target file already exists. It is intended to provide reusable workflow_call templates for consumers.
     *
     * @return void
     */
    private function createGitHubActionWorkflows(): void
    {
        $finder = Finder::create()
            ->files()
            ->in(parent::getDevToolsFile('resources/github-actions'))
            ->name('*.yml');

        foreach ($finder as $file) {
            $targetPath = Path::join('.github', 'workflows', $file->getFilename());

            if ($this->filesystem->exists($targetPath)) {
                continue;
            }

            $content = file_get_contents($file->getRealPath());
            $this->filesystem->dumpFile($targetPath, $content);
        }
    }

    /**
     * Installs or updates the .editorconfig file in the root project directory.
     *
     * This method copies the .editorconfig from the package resources to the project root,
     * always overwriting to ensure it is up to date.
     *
     * @return void
     */
    private function copyEditorConfig(): void
    {
        $source = parent::getDevToolsFile('.editorconfig');
        $target = parent::getConfigFile('.editorconfig', true);

        if ($this->filesystem->exists($target)) {
            return;
        }

        $content = file_get_contents($source);
        $this->filesystem->dumpFile($target, $content);
    }

    /**
     * Installs the dependabot.yml configuration file in the .github directory if it does not exist.
     *
     * This method copies the dependabot.yml from the package resources to .github/dependabot.yml if it is not already present.
     *
     * @return void
     */
    private function copyDependabotConfig(): void
    {
        $source = parent::getDevToolsFile('resources/dependabot.yml');
        $target = parent::getConfigFile('.github/dependabot.yml', true);

        if ($this->filesystem->exists($target)) {
            return;
        }

        $content = file_get_contents($source);
        $this->filesystem->dumpFile($target, $content);
    }

    /**
     * Ensures the repository wiki is added as a git submodule in .github/wiki.
     *
     * This method checks if the .github/wiki directory exists. If not, it adds the repository's wiki as a submodule
     * using the remote origin URL, replacing .git with .wiki.git. This allows automated documentation and wiki updates.
     *
     * @return void
     */
    private function addRepositoryWikiGitSubmodule(): void
    {
        $wikiSubmodulePath = parent::getConfigFile('.github/wiki', true);

        if ($this->filesystem->exists($wikiSubmodulePath)) {
            return;
        }

        $repositoryUrl = $this->getGitRepositoryUrl();
        $wikiRepoUrl = str_replace('.git', '.wiki.git', $repositoryUrl);

        $process = new Process([
            'git',
            'submodule',
            'add',
            $wikiRepoUrl,
            Path::makeRelative($wikiSubmodulePath, $this->getCurrentWorkingDirectory()),
        ]);
        $process->mustRun();
    }

    /**
     * Retrieves the git remote origin URL for the current repository.
     *
     * This method runs 'git config --get remote.origin.url' and returns the trimmed output.
     *
     * @return string The remote origin URL of the repository
     */
    private function getGitRepositoryUrl(): string
    {
        $process = new Process(['git', 'config', '--get', 'remote.origin.url']);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
