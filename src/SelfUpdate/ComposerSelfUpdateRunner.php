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

namespace FastForward\DevTools\SelfUpdate;

use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates DevTools through the active Composer executable.
 */
final readonly class ComposerSelfUpdateRunner implements SelfUpdateRunnerInterface
{
    private const string PACKAGE = 'fast-forward/dev-tools';

    /**
     * @param ProcessBuilderInterface $processBuilder the process builder used to assemble Composer update commands
     * @param ProcessQueueInterface $processQueue the queue used to execute the update process
     */
    public function __construct(
        private ProcessBuilderInterface $processBuilder,
        private ProcessQueueInterface $processQueue,
    ) {}

    /**
     * Updates the installed DevTools package.
     *
     * @param bool $global whether the update should target Composer's global project
     * @param OutputInterface $output the command output used by the update process
     *
     * @return int the Composer process status code
     */
    public function update(bool $global, OutputInterface $output): int
    {
        $command = $global ? 'composer global update' : 'composer update';
        $label = $global ? 'Updating global DevTools installation' : 'Updating project DevTools installation';

        $this->processQueue->add(
            process: $this->processBuilder
                ->withArgument(self::PACKAGE)
                ->build($command),
            label: $label,
        );

        return $this->processQueue->run($output);
    }
}
