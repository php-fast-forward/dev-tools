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
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Orchestrates dev-tools synchronization commands for the consumer repository.
 */
#[AsCommand(
    name: 'dev-tools:sync',
    description: 'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, .editorconfig, and .gitattributes in the root project.',
    help: 'This command runs the dedicated synchronization commands for composer.json, resources, wiki, Git metadata, skills, license, and Git hooks.'
)]
final class SyncCommand extends BaseCommand
{
    /**
     * Creates a new SyncCommand instance.
     *
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble dev-tools processes
     * @param ProcessQueueInterface $processQueue the queue used to execute synchronization commands
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
    ) {
        parent::__construct();
    }

    /**
     * Queues and executes synchronization commands.
     *
     * @param InputInterface $input the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int the status code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting dev-tools synchronization...</info>');

        $this->queueDevToolsCommand(['composer-json:update']);
        $this->queueDevToolsCommand(['copy-resource', '--source=resources/github-actions', '--target=.github/workflows'], true);
        $this->queueDevToolsCommand(['copy-resource', '--source=.editorconfig', '--target=.editorconfig'], true);
        $this->queueDevToolsCommand(['copy-resource', '--source=resources/dependabot.yml', '--target=.github/dependabot.yml'], true);
        $this->queueDevToolsCommand(['wiki', '--init'], true);
        $this->queueDevToolsCommand(['gitignore'], true);
        $this->queueDevToolsCommand(['gitattributes'], true);
        $this->queueDevToolsCommand(['skills'], true);
        $this->queueDevToolsCommand(['license'], true);
        $this->queueDevToolsCommand(['git-hooks'], true);

        return $this->processQueue->run($output);
    }

    /**
     * Adds a dev-tools command invocation to the process queue.
     *
     * @param list<string> $arguments the dev-tools command arguments
     * @param bool $detached whether the command MAY run detached from subsequent queue entries
     */
    private function queueDevToolsCommand(array $arguments, bool $detached = false): void
    {
        $processBuilder = $this->processBuilder;

        foreach ($arguments as $argument) {
            $processBuilder = $processBuilder->withArgument($argument);
        }

        $this->processQueue->add($processBuilder->build($this->devToolsBinary()), detached: $detached);
    }

    /**
     * Resolves the packaged dev-tools binary path.
     *
     * @return string the absolute binary path
     */
    private function devToolsBinary(): string
    {
        return Path::makeAbsolute('bin/dev-tools', \dirname(__DIR__, 3));
    }
}
